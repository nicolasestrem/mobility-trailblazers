<?php
/**
 * Jury Management Module for Mobility Trailblazers
 * 
 * @package MobilityTrailblazers
 * @subpackage Modules/JuryManagement
 * @since 1.0.0
 */

namespace MobilityTrailblazers\Modules;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Jury_Management
 * Handles comprehensive jury member management
 */
class MT_Jury_Management {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
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
        $this->init_hooks();
        $this->create_database_tables();
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mt_jury_activity_log';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        
        if ($table_exists != $table) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                jury_id bigint(20) NOT NULL,
                action varchar(50) NOT NULL,
                details text,
                user_id bigint(20),
                ip_address varchar(45),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY jury_id (jury_id),
                KEY action (action),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Log the table creation
            error_log("Mobility Trailblazers: Created jury activity log table: $table");
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Admin menu
        add_action('admin_menu', array($this, 'add_jury_management_menu'), 15);
        
        // AJAX handlers
        add_action('wp_ajax_mt_save_jury_member', array($this, 'ajax_save_jury_member'));
        add_action('wp_ajax_mt_delete_jury_member', array($this, 'ajax_delete_jury_member'));
        add_action('wp_ajax_mt_get_jury_member', array($this, 'ajax_get_jury_member'));
        add_action('wp_ajax_mt_toggle_jury_status', array($this, 'ajax_toggle_jury_status'));
        add_action('wp_ajax_mt_export_jury_data', array($this, 'ajax_export_jury_data'));
        add_action('wp_ajax_mt_import_jury_data', array($this, 'ajax_import_jury_data'));
        add_action('wp_ajax_mt_get_jury_statistics', array($this, 'ajax_get_jury_statistics'));
        add_action('wp_ajax_mt_send_jury_invitation', array($this, 'ajax_send_jury_invitation'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add capabilities
        add_action('init', array($this, 'add_jury_capabilities'));
        
        // Meta boxes for jury post type
        add_action('add_meta_boxes', array($this, 'add_jury_meta_boxes'));
        add_action('save_post_mt_jury', array($this, 'save_jury_meta'));
        
        // Custom columns for jury list
        add_filter('manage_mt_jury_posts_columns', array($this, 'add_jury_columns'));
        add_action('manage_mt_jury_posts_custom_column', array($this, 'render_jury_columns'), 10, 2);
        add_filter('manage_edit-mt_jury_sortable_columns', array($this, 'make_jury_columns_sortable'));
        
        // Bulk actions
        add_filter('bulk_actions-edit-mt_jury', array($this, 'add_jury_bulk_actions'));
        add_filter('handle_bulk_actions-edit-mt_jury', array($this, 'handle_jury_bulk_actions'), 10, 3);
    }
    
    /**
     * Add capabilities for jury management
     */
    public function add_jury_capabilities() {
        // Add capabilities to mt_award_admin role
        $mt_admin = get_role('mt_award_admin');
        if ($mt_admin) {
            $mt_admin->add_cap('manage_mt_jury_members');
            $mt_admin->add_cap('edit_mt_jury_member');
            $mt_admin->add_cap('delete_mt_jury_member');
            $mt_admin->add_cap('create_mt_jury_member');
            $mt_admin->add_cap('export_mt_jury_data');
            $mt_admin->add_cap('import_mt_jury_data');
            $mt_admin->add_cap('send_jury_invitations');
        }
        
        // Add capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_mt_jury_members');
            $admin->add_cap('edit_mt_jury_member');
            $admin->add_cap('delete_mt_jury_member');
            $admin->add_cap('create_mt_jury_member');
            $admin->add_cap('export_mt_jury_data');
            $admin->add_cap('import_mt_jury_data');
            $admin->add_cap('send_jury_invitations');
        }
    }
    
    /**
     * Add jury management submenu
     */
    public function add_jury_management_menu() {
        // Add submenu under MT Award System
        add_submenu_page(
            'mt-award-system',
            __('Jury Management', 'mobility-trailblazers'),
            __('Jury Management', 'mobility-trailblazers'),
            'manage_options',
            'mt-jury-management',
            array($this, 'render_jury_management_page')
        );
        
        // Add import/export submenu
        add_submenu_page(
            'mt-award-system',
            __('Jury Import/Export', 'mobility-trailblazers'),
            __('Jury Import/Export', 'mobility-trailblazers'),
            'manage_options',
            'mt-jury-import-export',
            array($this, 'render_import_export_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our pages
        if (!in_array($hook, array('mt-award-system_page_mt-jury-management', 'mt-award-system_page_mt-jury-import-export', 'post.php', 'post-new.php'))) {
            return;
        }
        
        // Check if we're on jury post type
        global $post_type;
        if (in_array($hook, array('post.php', 'post-new.php')) && 'mt_jury' !== $post_type) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'mt-jury-management',
            MT_PLUGIN_URL . 'assets/css/jury-management.css',
            array(),
            MT_PLUGIN_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'mt-jury-management',
            MT_PLUGIN_URL . 'assets/js/jury-management.js',
            array('jquery', 'wp-util', 'jquery-ui-dialog', 'jquery-ui-tabs'),
            MT_PLUGIN_VERSION,
            true
        );
        
        // Enqueue media uploader
        wp_enqueue_media();
        
        // Localize script
        wp_localize_script('mt-jury-management', 'mtJuryManagement', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_jury_management_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this jury member?', 'mobility-trailblazers'),
                'confirm_bulk_delete' => __('Are you sure you want to delete the selected jury members?', 'mobility-trailblazers'),
                'saving' => __('Saving...', 'mobility-trailblazers'),
                'saved' => __('Saved successfully!', 'mobility-trailblazers'),
                'error' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
                'sending' => __('Sending invitation...', 'mobility-trailblazers'),
                'sent' => __('Invitation sent successfully!', 'mobility-trailblazers'),
                'importing' => __('Importing data...', 'mobility-trailblazers'),
                'imported' => __('Data imported successfully!', 'mobility-trailblazers'),
                'exporting' => __('Preparing export...', 'mobility-trailblazers'),
                'select_file' => __('Please select a file to import', 'mobility-trailblazers'),
            )
        ));
    }
    
    /**
     * Render jury management page
     */
    public function render_jury_management_page() {
        if (!current_user_can('manage_mt_jury_members')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Get jury statistics
        $stats = $this->get_jury_statistics();
        
        ?>
        <div class="wrap mt-jury-management-wrap">
            <h1 class="wp-heading-inline"><?php _e('Jury Management', 'mobility-trailblazers'); ?></h1>
            <a href="<?php echo admin_url('post-new.php?post_type=mt_jury'); ?>" class="page-title-action"><?php _e('Add New Jury Member', 'mobility-trailblazers'); ?></a>
            
            <!-- Statistics Dashboard -->
            <div class="mt-jury-stats-dashboard">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_members']; ?></div>
                    <div class="stat-label"><?php _e('Total Members', 'mobility-trailblazers'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['active_members']; ?></div>
                    <div class="stat-label"><?php _e('Active Members', 'mobility-trailblazers'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_evaluations']; ?></div>
                    <div class="stat-label"><?php _e('Total Evaluations', 'mobility-trailblazers'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['pending_evaluations']; ?></div>
                    <div class="stat-label"><?php _e('Pending Evaluations', 'mobility-trailblazers'); ?></div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div id="mt-jury-tabs" class="mt-tabs">
                <ul>
                    <li><a href="#tab-overview"><?php _e('Overview', 'mobility-trailblazers'); ?></a></li>
                    <li><a href="#tab-assignments"><?php _e('Assignments', 'mobility-trailblazers'); ?></a></li>
                    <li><a href="#tab-invitations"><?php _e('Invitations', 'mobility-trailblazers'); ?></a></li>
                    <li><a href="#tab-activity"><?php _e('Activity Log', 'mobility-trailblazers'); ?></a></li>
                </ul>
                
                <!-- Overview Tab -->
                <div id="tab-overview">
                    <div class="mt-jury-filters">
                        <input type="text" id="mt-jury-search" placeholder="<?php _e('Search jury members...', 'mobility-trailblazers'); ?>" class="regular-text">
                        <select id="mt-jury-filter-status">
                            <option value=""><?php _e('All Status', 'mobility-trailblazers'); ?></option>
                            <option value="active"><?php _e('Active', 'mobility-trailblazers'); ?></option>
                            <option value="inactive"><?php _e('Inactive', 'mobility-trailblazers'); ?></option>
                            <option value="pending"><?php _e('Pending', 'mobility-trailblazers'); ?></option>
                        </select>
                        <select id="mt-jury-filter-role">
                            <option value=""><?php _e('All Roles', 'mobility-trailblazers'); ?></option>
                            <option value="lead"><?php _e('Lead Jury', 'mobility-trailblazers'); ?></option>
                            <option value="member"><?php _e('Jury Member', 'mobility-trailblazers'); ?></option>
                            <option value="guest"><?php _e('Guest Jury', 'mobility-trailblazers'); ?></option>
                        </select>
                        <button class="button" id="mt-jury-apply-filters"><?php _e('Apply Filters', 'mobility-trailblazers'); ?></button>
                        <button class="button" id="mt-jury-reset-filters"><?php _e('Reset', 'mobility-trailblazers'); ?></button>
                    </div>
                    
                    <div id="mt-jury-list-container">
                        <!-- Dynamic content loaded via AJAX -->
                        <?php $this->render_jury_list(); ?>
                    </div>
                </div>
                
                <!-- Assignments Tab -->
                <div id="tab-assignments">
                    <h3><?php _e('Jury Assignment Overview', 'mobility-trailblazers'); ?></h3>
                    <p><?php _e('Manage candidate assignments for each jury member.', 'mobility-trailblazers'); ?></p>
                    
                    <div id="mt-jury-assignments-grid">
                        <?php $this->render_assignments_grid(); ?>
                    </div>
                    
                    <div class="mt-assignment-actions">
                        <button class="button button-primary" id="mt-auto-assign"><?php _e('Auto-Assign Candidates', 'mobility-trailblazers'); ?></button>
                        <button class="button" id="mt-balance-assignments"><?php _e('Balance Assignments', 'mobility-trailblazers'); ?></button>
                        <button class="button" id="mt-clear-assignments"><?php _e('Clear All Assignments', 'mobility-trailblazers'); ?></button>
                    </div>
                </div>
                
                <!-- Invitations Tab -->
                <div id="tab-invitations">
                    <h3><?php _e('Send Jury Invitations', 'mobility-trailblazers'); ?></h3>
                    
                    <div class="mt-invitation-form">
                        <h4><?php _e('Bulk Invitation', 'mobility-trailblazers'); ?></h4>
                        <p><?php _e('Send invitations to all pending jury members.', 'mobility-trailblazers'); ?></p>
                        
                        <div class="mt-invitation-template">
                            <label for="invitation-subject"><?php _e('Email Subject', 'mobility-trailblazers'); ?></label>
                            <input type="text" id="invitation-subject" class="large-text" value="<?php echo esc_attr($this->get_invitation_template('subject')); ?>">
                            
                            <label for="invitation-message"><?php _e('Email Message', 'mobility-trailblazers'); ?></label>
                            <textarea id="invitation-message" rows="10" class="large-text"><?php echo esc_textarea($this->get_invitation_template('message')); ?></textarea>
                            
                            <p class="description"><?php _e('Available placeholders: {first_name}, {last_name}, {login_url}, {jury_dashboard_url}', 'mobility-trailblazers'); ?></p>
                        </div>
                        
                        <button class="button button-primary" id="mt-send-bulk-invitations"><?php _e('Send Invitations', 'mobility-trailblazers'); ?></button>
                    </div>
                    
                    <div class="mt-invitation-history">
                        <h4><?php _e('Invitation History', 'mobility-trailblazers'); ?></h4>
                        <?php $this->render_invitation_history(); ?>
                    </div>
                </div>
                
                <!-- Activity Log Tab -->
                <div id="tab-activity">
                    <h3><?php _e('Jury Activity Log', 'mobility-trailblazers'); ?></h3>
                    <?php $this->render_activity_log(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render jury list
     */
    private function render_jury_list() {
        $args = array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        // Apply filters if set
        if (!empty($_GET['status'])) {
            $args['meta_query'][] = array(
                'key' => '_mt_jury_status',
                'value' => sanitize_text_field($_GET['status'])
            );
        }
        
        $jury_members = get_posts($args);
        
        if (empty($jury_members)) {
            echo '<p>' . __('No jury members found.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </td>
                    <th><?php _e('Photo', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Name', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Email', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Organization', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Role', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Assignments', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jury_members as $member): 
                    $member_meta = $this->get_jury_member_meta($member->ID);
                    $assignments = $this->get_jury_assignments($member->ID);
                ?>
                <tr data-jury-id="<?php echo $member->ID; ?>">
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="jury_members[]" value="<?php echo $member->ID; ?>">
                    </th>
                    <td>
                        <?php if (!empty($member_meta['photo_url'])): ?>
                            <img src="<?php echo esc_url($member_meta['photo_url']); ?>" alt="" class="mt-jury-photo">
                        <?php else: ?>
                            <div class="mt-jury-avatar">
                                <?php echo $this->get_initials($member->post_title); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong>
                            <a href="<?php echo get_edit_post_link($member->ID); ?>">
                                <?php echo esc_html($member->post_title); ?>
                            </a>
                        </strong>
                    </td>
                    <td><?php echo esc_html($member_meta['email']); ?></td>
                    <td><?php echo esc_html($member_meta['organization']); ?></td>
                    <td>
                        <span class="mt-jury-role mt-role-<?php echo esc_attr($member_meta['jury_role']); ?>">
                            <?php echo $this->get_jury_role_label($member_meta['jury_role']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="mt-assignments-count">
                            <?php echo count($assignments); ?> / <?php echo $member_meta['max_assignments'] ?: 15; ?>
                        </span>
                    </td>
                    <td>
                        <span class="mt-jury-status mt-status-<?php echo esc_attr($member_meta['status']); ?>">
                            <?php echo $this->get_status_label($member_meta['status']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="button button-small mt-toggle-status" data-jury-id="<?php echo $member->ID; ?>" data-current-status="<?php echo esc_attr($member_meta['status']); ?>">
                            <?php echo $member_meta['status'] === 'active' ? __('Deactivate', 'mobility-trailblazers') : __('Activate', 'mobility-trailblazers'); ?>
                        </button>
                        <button class="button button-small mt-send-invitation" data-jury-id="<?php echo $member->ID; ?>">
                            <?php _e('Send Invitation', 'mobility-trailblazers'); ?>
                        </button>
                        <a href="<?php echo get_edit_post_link($member->ID); ?>" class="button button-small">
                            <?php _e('Edit', 'mobility-trailblazers'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="mt-jury-bulk-actions">
            <select id="mt-jury-bulk-action">
                <option value=""><?php _e('Bulk Actions', 'mobility-trailblazers'); ?></option>
                <option value="activate"><?php _e('Activate', 'mobility-trailblazers'); ?></option>
                <option value="deactivate"><?php _e('Deactivate', 'mobility-trailblazers'); ?></option>
                <option value="send_invitation"><?php _e('Send Invitation', 'mobility-trailblazers'); ?></option>
                <option value="delete"><?php _e('Delete', 'mobility-trailblazers'); ?></option>
            </select>
            <button class="button" id="mt-apply-bulk-action"><?php _e('Apply', 'mobility-trailblazers'); ?></button>
        </div>
        <?php
    }
    
    /**
     * Add meta boxes for jury post type
     */
    public function add_jury_meta_boxes() {
        // Basic Information
        add_meta_box(
            'mt_jury_basic_info',
            __('Basic Information', 'mobility-trailblazers'),
            array($this, 'render_basic_info_meta_box'),
            'mt_jury',
            'normal',
            'high'
        );
        
        // Contact Information
        add_meta_box(
            'mt_jury_contact_info',
            __('Contact Information', 'mobility-trailblazers'),
            array($this, 'render_contact_info_meta_box'),
            'mt_jury',
            'normal',
            'high'
        );
        
        // Professional Information
        add_meta_box(
            'mt_jury_professional_info',
            __('Professional Information', 'mobility-trailblazers'),
            array($this, 'render_professional_info_meta_box'),
            'mt_jury',
            'normal',
            'default'
        );
        
        // Jury Settings
        add_meta_box(
            'mt_jury_settings',
            __('Jury Settings', 'mobility-trailblazers'),
            array($this, 'render_jury_settings_meta_box'),
            'mt_jury',
            'side',
            'default'
        );
        
        // Activity Log
        add_meta_box(
            'mt_jury_activity',
            __('Activity Log', 'mobility-trailblazers'),
            array($this, 'render_activity_meta_box'),
            'mt_jury',
            'normal',
            'low'
        );
    }
    
    /**
     * Render basic info meta box
     */
    public function render_basic_info_meta_box($post) {
        wp_nonce_field('mt_save_jury_meta', 'mt_jury_meta_nonce');
        
        $first_name = get_post_meta($post->ID, '_mt_first_name', true);
        $last_name = get_post_meta($post->ID, '_mt_last_name', true);
        $photo_url = get_post_meta($post->ID, '_mt_photo_url', true);
        $bio = get_post_meta($post->ID, '_mt_bio', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="mt_first_name"><?php _e('First Name', 'mobility-trailblazers'); ?></label></th>
                <td><input type="text" id="mt_first_name" name="mt_first_name" value="<?php echo esc_attr($first_name); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="mt_last_name"><?php _e('Last Name', 'mobility-trailblazers'); ?></label></th>
                <td><input type="text" id="mt_last_name" name="mt_last_name" value="<?php echo esc_attr($last_name); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="mt_photo_url"><?php _e('Profile Photo', 'mobility-trailblazers'); ?></label></th>
                <td>
                    <input type="hidden" id="mt_photo_url" name="mt_photo_url" value="<?php echo esc_attr($photo_url); ?>">
                    <button type="button" class="button" id="mt_upload_photo"><?php _e('Upload Photo', 'mobility-trailblazers'); ?></button>
                    <button type="button" class="button" id="mt_remove_photo" <?php echo empty($photo_url) ? 'style="display:none;"' : ''; ?>><?php _e('Remove Photo', 'mobility-trailblazers'); ?></button>
                    <div id="mt_photo_preview">
                        <?php if (!empty($photo_url)): ?>
                            <img src="<?php echo esc_url($photo_url); ?>" style="max-width: 150px; margin-top: 10px;">
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="mt_bio"><?php _e('Biography', 'mobility-trailblazers'); ?></label></th>
                <td><textarea id="mt_bio" name="mt_bio" rows="5" class="large-text"><?php echo esc_textarea($bio); ?></textarea></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render contact info meta box
     */
    public function render_contact_info_meta_box($post) {
        $email = get_post_meta($post->ID, '_mt_email', true);
        $phone = get_post_meta($post->ID, '_mt_phone', true);
        $linkedin = get_post_meta($post->ID, '_mt_linkedin_url', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="mt_email"><?php _e('Email', 'mobility-trailblazers'); ?></label></th>
                <td><input type="email" id="mt_email" name="mt_email" value="<?php echo esc_attr($email); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="mt_phone"><?php _e('Phone', 'mobility-trailblazers'); ?></label></th>
                <td><input type="tel" id="mt_phone" name="mt_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="mt_linkedin_url"><?php _e('LinkedIn Profile', 'mobility-trailblazers'); ?></label></th>
                <td>
                    <input type="url" id="mt_linkedin_url" name="mt_linkedin_url" value="<?php echo esc_attr($linkedin); ?>" class="regular-text" placeholder="https://linkedin.com/in/username">
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render professional info meta box
     */
    public function render_professional_info_meta_box($post) {
        $organization = get_post_meta($post->ID, '_mt_organization', true);
        $position = get_post_meta($post->ID, '_mt_position', true);
        $expertise = get_post_meta($post->ID, '_mt_expertise', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="mt_organization"><?php _e('Organization', 'mobility-trailblazers'); ?></label></th>
                <td><input type="text" id="mt_organization" name="mt_organization" value="<?php echo esc_attr($organization); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="mt_position"><?php _e('Position/Title', 'mobility-trailblazers'); ?></label></th>
                <td><input type="text" id="mt_position" name="mt_position" value="<?php echo esc_attr($position); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="mt_expertise"><?php _e('Areas of Expertise', 'mobility-trailblazers'); ?></label></th>
                <td>
                    <textarea id="mt_expertise" name="mt_expertise" rows="4" class="large-text" placeholder="<?php _e('Describe areas of expertise, specializations, and relevant experience...', 'mobility-trailblazers'); ?>"><?php echo esc_textarea($expertise); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render jury settings meta box
     */
    public function render_jury_settings_meta_box($post) {
        $status = get_post_meta($post->ID, '_mt_jury_status', true) ?: 'active';
        $jury_role = get_post_meta($post->ID, '_mt_jury_role', true) ?: 'member';
        $voting_weight = get_post_meta($post->ID, '_mt_voting_weight', true) ?: 1;
        $max_assignments = get_post_meta($post->ID, '_mt_max_assignments', true) ?: 15;
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="mt_jury_status"><?php _e('Status', 'mobility-trailblazers'); ?></label></th>
                <td>
                    <select id="mt_jury_status" name="mt_jury_status">
                        <option value="active" <?php selected($status, 'active'); ?>><?php _e('Active', 'mobility-trailblazers'); ?></option>
                        <option value="inactive" <?php selected($status, 'inactive'); ?>><?php _e('Inactive', 'mobility-trailblazers'); ?></option>
                        <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pending', 'mobility-trailblazers'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="mt_jury_role"><?php _e('Jury Role', 'mobility-trailblazers'); ?></label></th>
                <td>
                    <select id="mt_jury_role" name="mt_jury_role">
                        <option value="member" <?php selected($jury_role, 'member'); ?>><?php _e('Jury Member', 'mobility-trailblazers'); ?></option>
                        <option value="lead" <?php selected($jury_role, 'lead'); ?>><?php _e('Lead Jury', 'mobility-trailblazers'); ?></option>
                        <option value="guest" <?php selected($jury_role, 'guest'); ?>><?php _e('Guest Jury', 'mobility-trailblazers'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="mt_voting_weight"><?php _e('Voting Weight', 'mobility-trailblazers'); ?></label></th>
                <td>
                    <input type="number" id="mt_voting_weight" name="mt_voting_weight" value="<?php echo esc_attr($voting_weight); ?>" min="1" max="10" class="small-text">
                    <p class="description"><?php _e('Weight of this jury member\'s vote (1-10)', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="mt_max_assignments"><?php _e('Max Assignments', 'mobility-trailblazers'); ?></label></th>
                <td>
                    <input type="number" id="mt_max_assignments" name="mt_max_assignments" value="<?php echo esc_attr($max_assignments); ?>" min="1" max="50" class="small-text">
                    <p class="description"><?php _e('Maximum number of candidates this jury member can evaluate', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render activity meta box
     */
    public function render_activity_meta_box($post) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mt_jury_activity_log';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        
        if ($table_exists != $table) {
            echo '<p>' . __('Activity log not available yet.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        $activities = $wpdb->get_results($wpdb->prepare("
            SELECT al.*, u.display_name as user_name
            FROM {$wpdb->prefix}mt_jury_activity_log al
            LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID
            WHERE al.jury_id = %d
            ORDER BY al.created_at DESC
            LIMIT 20
        ", $post->ID));
        
        if (empty($activities)) {
            echo '<p>' . __('No activity recorded yet.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        ?>
        <div class="mt-activity-log">
            <?php foreach ($activities as $activity): ?>
            <div class="activity-item">
                <div class="activity-header">
                    <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $activity->action))); ?></strong>
                    <span class="activity-date"><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $activity->created_at)); ?></span>
                </div>
                <?php if (!empty($activity->details)): ?>
                <div class="activity-details">
                    <?php echo esc_html($activity->details); ?>
                </div>
                <?php endif; ?>
                <div class="activity-meta">
                    <?php if ($activity->user_name): ?>
                        <small><?php printf(__('by %s', 'mobility-trailblazers'), esc_html($activity->user_name)); ?></small>
                    <?php else: ?>
                        <small><?php _e('by System', 'mobility-trailblazers'); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <style>
        .mt-activity-log .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .mt-activity-log .activity-item:last-child {
            border-bottom: none;
        }
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        .activity-date {
            font-size: 12px;
            color: #666;
        }
        .activity-details {
            margin: 5px 0;
            color: #333;
        }
        .activity-meta {
            font-size: 11px;
            color: #999;
        }
        </style>
        <?php
    }
    
    /**
     * Save jury meta data
     */
    public function save_jury_meta($post_id) {
        // Check nonce
        if (!isset($_POST['mt_jury_meta_nonce']) || !wp_verify_nonce($_POST['mt_jury_meta_nonce'], 'mt_save_jury_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save basic info
        if (isset($_POST['mt_first_name'])) {
            update_post_meta($post_id, '_mt_first_name', sanitize_text_field($_POST['mt_first_name']));
        }
        if (isset($_POST['mt_last_name'])) {
            update_post_meta($post_id, '_mt_last_name', sanitize_text_field($_POST['mt_last_name']));
        }
        if (isset($_POST['mt_photo_url'])) {
            update_post_meta($post_id, '_mt_photo_url', esc_url_raw($_POST['mt_photo_url']));
        }
        if (isset($_POST['mt_bio'])) {
            update_post_meta($post_id, '_mt_bio', wp_kses_post($_POST['mt_bio']));
        }
        
        // Save contact info
        if (isset($_POST['mt_email'])) {
            update_post_meta($post_id, '_mt_email', sanitize_email($_POST['mt_email']));
        }
        if (isset($_POST['mt_phone'])) {
            update_post_meta($post_id, '_mt_phone', sanitize_text_field($_POST['mt_phone']));
        }
        if (isset($_POST['mt_linkedin_url'])) {
            update_post_meta($post_id, '_mt_linkedin_url', esc_url_raw($_POST['mt_linkedin_url']));
        }
        
        // Save professional info
        if (isset($_POST['mt_organization'])) {
            update_post_meta($post_id, '_mt_organization', sanitize_text_field($_POST['mt_organization']));
        }
        if (isset($_POST['mt_position'])) {
            update_post_meta($post_id, '_mt_position', sanitize_text_field($_POST['mt_position']));
        }
        if (isset($_POST['mt_expertise'])) {
            update_post_meta($post_id, '_mt_expertise', sanitize_textarea_field($_POST['mt_expertise']));
        }
        
        // Save jury settings
        if (isset($_POST['mt_jury_status'])) {
            update_post_meta($post_id, '_mt_jury_status', sanitize_text_field($_POST['mt_jury_status']));
        }
        if (isset($_POST['mt_jury_role'])) {
            update_post_meta($post_id, '_mt_jury_role', sanitize_text_field($_POST['mt_jury_role']));
        }
        if (isset($_POST['mt_voting_weight'])) {
            update_post_meta($post_id, '_mt_voting_weight', intval($_POST['mt_voting_weight']));
        }
        if (isset($_POST['mt_max_assignments'])) {
            update_post_meta($post_id, '_mt_max_assignments', intval($_POST['mt_max_assignments']));
        }
        
        // Log activity
        $this->log_activity($post_id, 'updated', 'Jury member details updated');
        
        // Update post title based on name
        if (isset($_POST['mt_first_name']) && isset($_POST['mt_last_name'])) {
            $full_name = trim($_POST['mt_first_name'] . ' ' . $_POST['mt_last_name']);
            if (!empty($full_name)) {
                // Remove action to prevent infinite loop
                remove_action('save_post_mt_jury', array($this, 'save_jury_meta'));
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_title' => $full_name
                ));
                add_action('save_post_mt_jury', array($this, 'save_jury_meta'));
            }
        }
    }
    
    /**
     * AJAX handler for toggling jury status
     */
    public function ajax_toggle_jury_status() {
        check_ajax_referer('mt_jury_management_nonce', 'nonce');
        
        if (!current_user_can('edit_mt_jury_member')) {
            wp_send_json_error(__('Insufficient permissions', 'mobility-trailblazers'));
        }
        
        $jury_id = intval($_POST['jury_id']);
        $current_status = sanitize_text_field($_POST['current_status']);
        
        $new_status = $current_status === 'active' ? 'inactive' : 'active';
        
        update_post_meta($jury_id, '_mt_jury_status', $new_status);
        
        // Log activity
        $this->log_activity($jury_id, 'status_changed', sprintf('Status changed from %s to %s', $current_status, $new_status));
        
        wp_send_json_success(array(
            'message' => __('Status updated successfully', 'mobility-trailblazers'),
            'new_status' => $new_status
        ));
    }
    
    /**
     * Get jury statistics
     */
    private function get_jury_statistics() {
        global $wpdb;
        
        $total_members = wp_count_posts('mt_jury')->publish;
        
        $active_members = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = %s 
            AND p.post_status = %s
            AND pm.meta_key = %s 
            AND pm.meta_value = %s
        ", 'mt_jury', 'publish', '_mt_jury_status', 'active'));
        
        // Get evaluation statistics
        $total_evaluations = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}mt_evaluations
        ");
        
        $pending_evaluations = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT post_id)
            FROM {$wpdb->postmeta}
            WHERE meta_key = %s
            AND meta_value = %s
        ", '_mt_evaluation_status', 'pending'));
        
        return array(
            'total_members' => $total_members,
            'active_members' => $active_members,
            'total_evaluations' => $total_evaluations,
            'pending_evaluations' => $pending_evaluations
        );
    }
    
    /**
     * Log jury activity
     */
    private function log_activity($jury_id, $action, $details = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mt_jury_activity_log';
        
        // Insert log entry
        $wpdb->insert($table, array(
            'jury_id' => $jury_id,
            'action' => $action,
            'details' => $details,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ));
    }
    
    /**
     * Get jury member meta data
     */
    private function get_jury_member_meta($jury_id) {
        return array(
            'first_name' => get_post_meta($jury_id, '_mt_first_name', true),
            'last_name' => get_post_meta($jury_id, '_mt_last_name', true),
            'email' => get_post_meta($jury_id, '_mt_email', true),
            'phone' => get_post_meta($jury_id, '_mt_phone', true),
            'organization' => get_post_meta($jury_id, '_mt_organization', true),
            'position' => get_post_meta($jury_id, '_mt_position', true),
            'photo_url' => get_post_meta($jury_id, '_mt_photo_url', true),
            'bio' => get_post_meta($jury_id, '_mt_bio', true),
            'expertise' => get_post_meta($jury_id, '_mt_expertise', true),
            'linkedin_url' => get_post_meta($jury_id, '_mt_linkedin_url', true),
            'status' => get_post_meta($jury_id, '_mt_jury_status', true) ?: 'active',
            'jury_role' => get_post_meta($jury_id, '_mt_jury_role', true) ?: 'member',
            'voting_weight' => get_post_meta($jury_id, '_mt_voting_weight', true) ?: 1,
            'max_assignments' => get_post_meta($jury_id, '_mt_max_assignments', true) ?: 15,
        );
    }
    
    /**
     * Get jury assignments
     */
    private function get_jury_assignments($jury_id) {
        return get_posts(array(
            'post_type' => 'mt_candidate',
            'meta_query' => array(
                array(
                    'key' => '_mt_assigned_jury_member',
                    'value' => $jury_id
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
    }
    
    /**
     * Get initials from name
     */
    private function get_initials($name) {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        return substr($initials, 0, 2);
    }
    
    /**
     * Get jury role label
     */
    private function get_jury_role_label($role) {
        $roles = array(
            'lead' => __('Lead Jury', 'mobility-trailblazers'),
            'member' => __('Jury Member', 'mobility-trailblazers'),
            'guest' => __('Guest Jury', 'mobility-trailblazers')
        );
        return isset($roles[$role]) ? $roles[$role] : $role;
    }
    
    /**
     * Get status label
     */
    private function get_status_label($status) {
        $statuses = array(
            'active' => __('Active', 'mobility-trailblazers'),
            'inactive' => __('Inactive', 'mobility-trailblazers'),
            'pending' => __('Pending', 'mobility-trailblazers')
        );
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
    
    /**
     * Render assignments grid
     */
    private function render_assignments_grid() {
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        if (empty($jury_members) || empty($candidates)) {
            echo '<p>' . __('No jury members or candidates found for assignment.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        ?>
        <div class="mt-assignments-grid">
            <div class="assignments-header">
                <div class="jury-column">
                    <h4><?php _e('Jury Members', 'mobility-trailblazers'); ?></h4>
                </div>
                <div class="candidates-column">
                    <h4><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></h4>
                </div>
            </div>
            
            <?php foreach ($jury_members as $jury): 
                $assignments = $this->get_jury_assignments($jury->ID);
                $member_meta = $this->get_jury_member_meta($jury->ID);
            ?>
            <div class="assignment-row" data-jury-id="<?php echo $jury->ID; ?>">
                <div class="jury-info">
                    <strong><?php echo esc_html($jury->post_title); ?></strong>
                    <span class="assignment-count">(<?php echo count($assignments); ?>/<?php echo $member_meta['max_assignments']; ?>)</span>
                    <div class="jury-meta">
                        <small><?php echo esc_html($member_meta['organization']); ?></small>
                    </div>
                </div>
                <div class="assigned-candidates">
                    <?php if (!empty($assignments)): ?>
                        <?php foreach ($assignments as $candidate_id): 
                            $candidate = get_post($candidate_id);
                            if ($candidate):
                        ?>
                        <span class="assigned-candidate" data-candidate-id="<?php echo $candidate_id; ?>">
                            <?php echo esc_html($candidate->post_title); ?>
                            <button class="remove-assignment" data-jury-id="<?php echo $jury->ID; ?>" data-candidate-id="<?php echo $candidate_id; ?>">×</button>
                        </span>
                        <?php endif; endforeach; ?>
                    <?php else: ?>
                        <span class="no-assignments"><?php _e('No assignments', 'mobility-trailblazers'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="unassigned-candidates">
            <h4><?php _e('Unassigned Candidates', 'mobility-trailblazers'); ?></h4>
            <div class="candidates-pool">
                <?php 
                $assigned_candidate_ids = array();
                foreach ($jury_members as $jury) {
                    $assignments = $this->get_jury_assignments($jury->ID);
                    $assigned_candidate_ids = array_merge($assigned_candidate_ids, $assignments);
                }
                
                foreach ($candidates as $candidate):
                    if (!in_array($candidate->ID, $assigned_candidate_ids)):
                ?>
                <span class="unassigned-candidate" data-candidate-id="<?php echo $candidate->ID; ?>">
                    <?php echo esc_html($candidate->post_title); ?>
                </span>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get invitation template
     */
    private function get_invitation_template($type = 'subject') {
        $templates = array(
            'subject' => __('Invitation to Join Mobility Trailblazers Jury', 'mobility-trailblazers'),
            'message' => __('Dear {first_name} {last_name},

You have been invited to join the Mobility Trailblazers jury panel. We are excited to have your expertise contribute to selecting the most innovative mobility solutions.

Please log in to your jury dashboard to get started:
{jury_dashboard_url}

If you need to access your account, please use this login URL:
{login_url}

Thank you for your participation!

Best regards,
The Mobility Trailblazers Team', 'mobility-trailblazers')
        );
        
        return isset($templates[$type]) ? $templates[$type] : '';
    }
    
    /**
     * Render invitation history
     */
    private function render_invitation_history() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mt_jury_activity_log';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        
        if ($table_exists != $table) {
            echo '<p>' . __('Activity log table not found. Please refresh the page.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        $invitations = $wpdb->get_results($wpdb->prepare("
            SELECT al.*, p.post_title as jury_name
            FROM {$wpdb->prefix}mt_jury_activity_log al
            INNER JOIN {$wpdb->posts} p ON al.jury_id = p.ID
            WHERE al.action = %s
            ORDER BY al.created_at DESC
            LIMIT 50
        ", 'invitation_sent'));
        
        if (empty($invitations)) {
            echo '<p>' . __('No invitations sent yet.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Date Sent', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Details', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invitations as $invitation): ?>
                <tr>
                    <td><?php echo esc_html($invitation->jury_name); ?></td>
                    <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $invitation->created_at)); ?></td>
                    <td><?php echo esc_html($invitation->details); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Render activity log
     */
    private function render_activity_log() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mt_jury_activity_log';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        
        if ($table_exists != $table) {
            echo '<p>' . __('Activity log table not found. Please refresh the page.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        $activities = $wpdb->get_results("
            SELECT al.*, p.post_title as jury_name, u.display_name as user_name
            FROM {$wpdb->prefix}mt_jury_activity_log al
            INNER JOIN {$wpdb->posts} p ON al.jury_id = p.ID
            LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID
            ORDER BY al.created_at DESC
            LIMIT 100
        ");
        
        if (empty($activities)) {
            echo '<p>' . __('No activity recorded yet.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date/Time', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Action', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Details', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('User', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $activity): ?>
                <tr>
                    <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $activity->created_at)); ?></td>
                    <td><?php echo esc_html($activity->jury_name); ?></td>
                    <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $activity->action))); ?></td>
                    <td><?php echo esc_html($activity->details); ?></td>
                    <td><?php echo esc_html($activity->user_name ?: __('System', 'mobility-trailblazers')); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Render import/export page
     */
    public function render_import_export_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Jury Import/Export', 'mobility-trailblazers'); ?></h1>
            
            <div class="mt-import-export-container">
                <div class="mt-export-section">
                    <h2><?php _e('Export Jury Data', 'mobility-trailblazers'); ?></h2>
                    <p><?php _e('Export all jury member data to a CSV file.', 'mobility-trailblazers'); ?></p>
                    
                    <button class="button button-primary" id="mt-export-jury-data">
                        <?php _e('Export Jury Data', 'mobility-trailblazers'); ?>
                    </button>
                </div>
                
                <div class="mt-import-section">
                    <h2><?php _e('Import Jury Data', 'mobility-trailblazers'); ?></h2>
                    <p><?php _e('Import jury member data from a CSV file.', 'mobility-trailblazers'); ?></p>
                    
                    <form id="mt-import-jury-form" enctype="multipart/form-data">
                        <input type="file" id="mt-jury-import-file" accept=".csv" required>
                        <button type="submit" class="button button-primary">
                            <?php _e('Import Jury Data', 'mobility-trailblazers'); ?>
                        </button>
                    </form>
                    
                    <div class="mt-import-template">
                        <h3><?php _e('CSV Template', 'mobility-trailblazers'); ?></h3>
                        <p><?php _e('Your CSV file should have the following columns:', 'mobility-trailblazers'); ?></p>
                        <code>first_name,last_name,email,phone,organization,position,expertise,jury_role,status</code>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the module
add_action('plugins_loaded', function() {
    if (class_exists('MobilityTrailblazers\Modules\MT_Jury_Management')) {
        MT_Jury_Management::get_instance();
    }
});