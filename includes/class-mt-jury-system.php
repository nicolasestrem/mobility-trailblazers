<?php
/**
 * Jury System handler class
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Jury_System
 * Handles jury-specific functionality
 */
class MT_Jury_System {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add jury dashboard scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_jury_dashboard_scripts'));
        
        // Add jury member meta to user profile
        add_action('show_user_profile', array($this, 'add_jury_fields'));
        add_action('edit_user_profile', array($this, 'add_jury_fields'));
        add_action('personal_options_update', array($this, 'save_jury_fields'));
        add_action('edit_user_profile_update', array($this, 'save_jury_fields'));
        
        // Auto-create user accounts for jury members
        add_action('save_post_mt_jury_member', array($this, 'maybe_create_user_account'), 10, 3);
        
        // Sync jury member data with user
        add_action('profile_update', array($this, 'sync_user_to_jury_member'), 10, 2);
        
        // Add dashboard widget for jury members
        add_action('wp_dashboard_setup', array($this, 'add_jury_dashboard_widget'));
        
        // Handle jury member login redirect
        add_filter('login_redirect', array($this, 'jury_login_redirect'), 10, 3);
        
        // Add body class for jury members
        add_filter('body_class', array($this, 'add_jury_body_class'));
        
        // Restrict jury member access
        add_action('template_redirect', array($this, 'restrict_jury_access'));
    }
    
    /**
     * Enqueue jury dashboard scripts
     */
    public function enqueue_jury_dashboard_scripts() {
        // Check if we're on a page with jury dashboard shortcode
        global $post;
        
        if (!is_a($post, 'WP_Post')) {
            return;
        }
        
        if (has_shortcode($post->post_content, 'mt_jury_dashboard')) {
            // Enqueue dashboard styles
            wp_enqueue_style(
                'mt-jury-dashboard',
                MT_PLUGIN_URL . 'assets/frontend.css',
                array(),
                MT_PLUGIN_VERSION
            );
            
            // Add inline styles as fallback
            $inline_styles = "
                .mt-jury-dashboard {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .mt-dashboard-header {
                    background: #fff;
                    padding: 20px;
                    margin-bottom: 20px;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                }
                .mt-dashboard-stats {
                    display: flex;
                    justify-content: space-between;
                    gap: 20px;
                    margin-bottom: 20px;
                }
                .mt-stat-box {
                    flex: 1;
                    background: #fff;
                    padding: 20px;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                    text-align: center;
                }
                .mt-stat-number {
                    display: block;
                    font-size: 24px;
                    font-weight: 600;
                    color: #2271b1;
                }
                .mt-stat-label {
                    display: block;
                    font-size: 14px;
                    color: #646970;
                    margin-top: 5px;
                }
                .mt-progress-section {
                    background: #fff;
                    padding: 20px;
                    margin-bottom: 20px;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                }
                .mt-progress-bar {
                    height: 20px;
                    background: #f0f0f1;
                    border-radius: 10px;
                    overflow: hidden;
                    position: relative;
                }
                .mt-progress-fill {
                    height: 100%;
                    background: #2271b1;
                    transition: width 0.3s ease;
                }
                .mt-progress-text {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    color: #fff;
                    font-weight: 600;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
                }
            ";
            wp_add_inline_style('mt-jury-dashboard', $inline_styles);
            
            // Enqueue dashboard scripts
            wp_enqueue_script(
                'mt-jury-dashboard',
                MT_PLUGIN_URL . 'assets/jury-dashboard.js',
                array('jquery'),
                MT_PLUGIN_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('mt-jury-dashboard', 'mt_jury_dashboard', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_jury_dashboard'),
                'i18n' => array(
                    'loading_evaluation' => __('Loading evaluation...', 'mobility-trailblazers'),
                    'evaluation_loaded' => __('Evaluation loaded successfully', 'mobility-trailblazers'),
                    'error_loading' => __('Error loading evaluation', 'mobility-trailblazers'),
                    'submitting' => __('Submitting evaluation...', 'mobility-trailblazers'),
                    'submit_evaluation' => __('Submit Evaluation', 'mobility-trailblazers'),
                    'evaluation_submitted' => __('Evaluation submitted successfully!', 'mobility-trailblazers'),
                    'error_submitting' => __('Error submitting evaluation', 'mobility-trailblazers'),
                    'network_error' => __('Network error. Please try again.', 'mobility-trailblazers'),
                    'evaluated' => __('Evaluated', 'mobility-trailblazers'),
                    'please_rate_all' => __('Please rate all criteria before submitting', 'mobility-trailblazers'),
                    'saving' => __('Saving draft...', 'mobility-trailblazers'),
                    'save_draft' => __('Save as Draft', 'mobility-trailblazers'),
                    'draft_saved' => __('Draft saved successfully!', 'mobility-trailblazers'),
                    'error_saving' => __('Error saving draft', 'mobility-trailblazers'),
                    'all_complete' => __('Congratulations! You have completed all evaluations!', 'mobility-trailblazers'),
                    'preparing_export' => __('Preparing export...', 'mobility-trailblazers'),
                    'export_complete' => __('Export ready! Download will start shortly.', 'mobility-trailblazers'),
                    'export_error' => __('Error preparing export', 'mobility-trailblazers'),
                    'unsaved_changes' => __('You have unsaved changes. Are you sure you want to leave?', 'mobility-trailblazers'),
                    'confirm_submit' => __('Are you sure you want to submit this evaluation?', 'mobility-trailblazers'),
                    'confirm_export' => __('Are you sure you want to export your evaluations?', 'mobility-trailblazers'),
                ),
            ));
        }
    }
    
    /**
     * Add jury profile fields to user profile
     *
     * @param WP_User $user User object
     */
    public function add_jury_fields($user) {
        $jury_member_id = get_user_meta($user->ID, '_mt_jury_member_id', true);
        ?>
        <h3><?php _e('Jury Member Information', 'mobility-trailblazers'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="mt_jury_member_id"><?php _e('Linked Jury Member', 'mobility-trailblazers'); ?></label></th>
                <td>
                    <?php
                    $jury_members = get_posts(array(
                        'post_type' => mt_get_jury_post_type(),
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    ?>
                    <select name="mt_jury_member_id" id="mt_jury_member_id">
                        <option value=""><?php _e('— Select Jury Member —', 'mobility-trailblazers'); ?></option>
                        <?php foreach ($jury_members as $member) : ?>
                            <option value="<?php echo $member->ID; ?>" <?php selected($jury_member_id, $member->ID); ?>>
                                <?php echo esc_html($member->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save jury profile fields
     *
     * @param int $user_id User ID
     */
    public function save_jury_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        if (isset($_POST['mt_jury_member_id'])) {
            $jury_member_id = intval($_POST['mt_jury_member_id']);
            
            // Update the jury member link
            update_user_meta($user_id, '_mt_jury_member_id', $jury_member_id);
            
            // Add jury role if needed
            $user = get_user_by('id', $user_id);
            if ($user && !in_array('mt_jury_member', $user->roles)) {
                $user->add_role('mt_jury_member');
            }
            
            // Remove old jury member link if changed
            $old_jury_member_id = get_user_meta($user_id, '_mt_jury_member_id', true);
            if ($old_jury_member_id && $old_jury_member_id !== $jury_member_id) {
                delete_user_meta($user_id, '_mt_jury_member_id');
            }
        }
    }
    
    /**
     * Maybe create user account when jury member is created
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update
     */
    public function maybe_create_user_account($post_id, $post, $update) {
        // Skip if autosave or revision
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        // Check if auto-create is enabled
        if (!get_option('mt_auto_create_jury_users', false)) {
            return;
        }
        
        // Check if user already exists
        $user_id = get_post_meta($post_id, '_mt_user_id', true);
        if ($user_id) {
            return;
        }
        
        // Get email
        $email = get_post_meta($post_id, '_mt_email', true);
        if (!$email || !is_email($email)) {
            return;
        }
        
        // Create user account
        $result = MT_Roles::create_jury_user($post_id, array(
            'send_notification' => !$update, // Only send notification for new jury members
        ));
        
        if (!is_wp_error($result)) {
            // Add admin notice
            add_filter('redirect_post_location', function($location) {
                return add_query_arg('mt_user_created', '1', $location);
            });
        }
    }
    
    /**
     * Sync user data to jury member
     *
     * @param int $user_id User ID
     * @param array $old_user_data Old user data
     */
    public function sync_user_to_jury_member($user_id, $old_user_data) {
        $jury_member_id = get_user_meta($user_id, '_mt_jury_member_id', true);
        
        if (!$jury_member_id) {
            return;
        }
        
        $user = get_user_by('id', $user_id);
        
        // Update jury member email
        update_post_meta($jury_member_id, '_mt_email', $user->user_email);
        
        // Update jury member title if changed
        if ($user->display_name !== $old_user_data->display_name) {
            wp_update_post(array(
                'ID' => $jury_member_id,
                'post_title' => $user->display_name,
            ));
        }
    }
    
    /**
     * Add jury dashboard widget
     */
    public function add_jury_dashboard_widget() {
        if (!mt_is_jury_member()) {
            return;
        }
        
        wp_add_dashboard_widget(
            'mt_jury_dashboard_widget',
            __('Jury Member Dashboard', 'mobility-trailblazers'),
            array($this, 'render_jury_dashboard_widget')
        );
    }
    
    /**
     * Render jury dashboard widget
     */
    public function render_jury_dashboard_widget() {
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        
        if (!$jury_member) {
            echo '<p>' . __('Jury member profile not found.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        // Get statistics
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        $completed_evaluations = 0;
        
        foreach ($assigned_candidates as $candidate_id) {
            if (mt_has_evaluated($candidate_id, $jury_member->ID)) {
                $completed_evaluations++;
            }
        }
        
        $completion_rate = count($assigned_candidates) > 0 
            ? round(($completed_evaluations / count($assigned_candidates)) * 100) 
            : 0;
        ?>
        
        <div class="mt-jury-widget">
            <div class="mt-widget-stats">
                <div class="mt-stat">
                    <span class="mt-stat-number"><?php echo count($assigned_candidates); ?></span>
                    <span class="mt-stat-label"><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></span>
                </div>
                
                <div class="mt-stat">
                    <span class="mt-stat-number"><?php echo $completed_evaluations; ?></span>
                    <span class="mt-stat-label"><?php _e('Completed', 'mobility-trailblazers'); ?></span>
                </div>
                
                <div class="mt-stat">
                    <span class="mt-stat-number"><?php echo $completion_rate; ?>%</span>
                    <span class="mt-stat-label"><?php _e('Progress', 'mobility-trailblazers'); ?></span>
                </div>
            </div>
            
            <div class="mt-widget-progress">
                <div class="mt-progress-bar">
                    <div class="mt-progress-fill" style="width: <?php echo $completion_rate; ?>%;"></div>
                </div>
            </div>
            
            <div class="mt-widget-actions">
                <a href="<?php echo admin_url('admin.php?page=mt-jury-dashboard'); ?>" class="button button-primary">
                    <?php _e('Go to Jury Dashboard', 'mobility-trailblazers'); ?>
                </a>
                
                <?php
                $frontend_page = get_option('mt_jury_dashboard_page');
                if ($frontend_page) :
                ?>
                    <a href="<?php echo get_permalink($frontend_page); ?>" class="button">
                        <?php _e('Frontend Dashboard', 'mobility-trailblazers'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .mt-jury-widget {
            padding: 10px 0;
        }
        
        .mt-widget-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .mt-stat {
            text-align: center;
            flex: 1;
        }
        
        .mt-stat-number {
            display: block;
            font-size: 24px;
            font-weight: 600;
            color: #0073aa;
        }
        
        .mt-stat-label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .mt-widget-progress {
            margin-bottom: 20px;
        }
        
        .mt-progress-bar {
            height: 10px;
            background: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .mt-progress-fill {
            height: 100%;
            background: #0073aa;
            transition: width 0.3s ease;
        }
        
        .mt-widget-actions {
            display: flex;
            gap: 10px;
        }
        
        .mt-widget-actions .button {
            flex: 1;
            text-align: center;
        }
        </style>
        
        <?php
    }
    
    /**
     * Jury login redirect
     *
     * @param string $redirect_to Redirect URL
     * @param string $requested_redirect_to Requested redirect URL
     * @param WP_User|WP_Error $user User object or error
     * @return string Redirect URL
     */
    public function jury_login_redirect($redirect_to, $requested_redirect_to, $user) {
        if (is_wp_error($user)) {
            return $redirect_to;
        }
        
        if (in_array('mt_jury_member', $user->roles)) {
            // Check if frontend dashboard page is set
            $frontend_page = get_option('mt_jury_dashboard_page');
            if ($frontend_page) {
                return get_permalink($frontend_page);
            }
            
            // Otherwise redirect to admin dashboard
            return admin_url('admin.php?page=mt-jury-dashboard');
        }
        
        return $redirect_to;
    }
    
    /**
     * Add body class for jury members
     *
     * @param array $classes Body classes
     * @return array Modified body classes
     */
    public function add_jury_body_class($classes) {
        if (mt_is_jury_member()) {
            $classes[] = 'mt-jury-member';
        }
        
        return $classes;
    }
    
    /**
     * Restrict jury member access
     */
    public function restrict_jury_access() {
        if (!is_user_logged_in() || !mt_is_jury_member()) {
            return;
        }
        
        // Get restricted pages
        $restricted_pages = get_option('mt_jury_restricted_pages', array());
        
        if (empty($restricted_pages)) {
            return;
        }
        
        if (is_page($restricted_pages)) {
            // Redirect to jury dashboard
            $frontend_page = get_option('mt_jury_dashboard_page');
            if ($frontend_page) {
                wp_redirect(get_permalink($frontend_page));
            } else {
                wp_redirect(admin_url('admin.php?page=mt-jury-dashboard'));
            }
            exit;
        }
    }
    
    /**
     * Get jury member statistics
     *
     * @param int $jury_member_id Jury member ID
     * @return array Statistics array
     */
    public static function get_jury_statistics($jury_member_id) {
        global $wpdb;
        
        $stats = array(
            'assigned_candidates' => 0,
            'completed_evaluations' => 0,
            'pending_evaluations' => 0,
            'average_score_given' => 0,
            'completion_rate' => 0,
            'last_activity' => null,
        );
        
        // Get assigned candidates
        $assigned_candidates = mt_get_assigned_candidates($jury_member_id);
        $stats['assigned_candidates'] = count($assigned_candidates);
        
        if ($stats['assigned_candidates'] > 0) {
            // Count completed evaluations
            $table_name = $wpdb->prefix . 'mt_candidate_scores';
            
            $completed = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                 WHERE jury_member_id = %d AND is_active = 1",
                $jury_member_id
            ));
            
            $stats['completed_evaluations'] = $completed;
            $stats['pending_evaluations'] = $stats['assigned_candidates'] - $completed;
            $stats['completion_rate'] = round(($completed / $stats['assigned_candidates']) * 100);
            
            // Get average score given
            $avg_score = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(total_score) FROM $table_name 
                 WHERE jury_member_id = %d AND is_active = 1",
                $jury_member_id
            ));
            
            $stats['average_score_given'] = $avg_score ? round($avg_score, 1) : 0;
            
            // Get last activity
            $last_activity = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(evaluation_date) FROM $table_name 
                 WHERE jury_member_id = %d",
                $jury_member_id
            ));
            
            $stats['last_activity'] = $last_activity;
        }
        
        return $stats;
    }
    
    /**
     * Send reminder to jury member
     *
     * @param int $jury_member_id Jury member ID
     * @param string $type Reminder type
     * @return bool Whether email was sent
     */
    public static function send_jury_reminder($jury_member_id, $type = 'evaluation') {
        $jury_member = get_post($jury_member_id);
        
        if (!$jury_member || $jury_member->post_type !== 'mt_jury_member') {
            return false;
        }
        
        $user_id = get_post_meta($jury_member_id, '_mt_user_id', true);
        if (!$user_id) {
            return false;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Get statistics
        $stats = self::get_jury_statistics($jury_member_id);
        
        if ($stats['pending_evaluations'] === 0) {
            return false; // No pending evaluations
        }
        
        // Prepare email
        $subject = sprintf(
            __('Reminder: %d Evaluations Pending - Mobility Trailblazers Award', 'mobility-trailblazers'),
            $stats['pending_evaluations']
        );
        
        $message = sprintf(
            __('Dear %s,

This is a friendly reminder that you have %d candidate evaluations pending for the Mobility Trailblazers Award.

Your Progress:
- Assigned Candidates: %d
- Completed Evaluations: %d
- Pending Evaluations: %d
- Completion Rate: %d%%

The evaluation deadline is %s.

Please log in to your jury dashboard to complete your evaluations:
%s

If you have any questions or need assistance, please don\'t hesitate to contact us.

Best regards,
%s', 'mobility-trailblazers'),
            $jury_member->post_title,
            $stats['pending_evaluations'],
            $stats['assigned_candidates'],
            $stats['completed_evaluations'],
            $stats['pending_evaluations'],
            $stats['completion_rate'],
            mt_format_date(get_option('mt_evaluation_deadline')),
            admin_url('admin.php?page=mt-jury-dashboard'),
            get_bloginfo('name')
        );
        
        // Send email
        $sent = mt_send_email($user->user_email, $subject, $message);
        
        if ($sent) {
            // Log reminder
            update_post_meta($jury_member_id, '_mt_last_reminder_sent', current_time('mysql'));
            
            mt_log('Jury reminder sent', 'info', array(
                'jury_member_id' => $jury_member_id,
                'type' => $type,
                'pending_evaluations' => $stats['pending_evaluations'],
            ));
        }
        
        return $sent;
    }
    
    /**
     * Send bulk reminders to jury members
     *
     * @param array $criteria Criteria for selecting jury members
     * @return array Results array
     */
    public static function send_bulk_reminders($criteria = array()) {
        $defaults = array(
            'min_pending' => 1,
            'completion_below' => 100,
            'inactive_days' => 7,
        );
        
        $criteria = wp_parse_args($criteria, $defaults);
        
        // Get all jury members
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury_member',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));
        
        $results = array(
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        );
        
        foreach ($jury_members as $jury_member) {
            // Get statistics
            $stats = self::get_jury_statistics($jury_member->ID);
            
            // Check criteria
            if ($stats['pending_evaluations'] < $criteria['min_pending']) {
                $results['skipped']++;
                continue;
            }
            
            if ($stats['completion_rate'] >= $criteria['completion_below']) {
                $results['skipped']++;
                continue;
            }
            
            // Check last reminder
            $last_reminder = get_post_meta($jury_member->ID, '_mt_last_reminder_sent', true);
            if ($last_reminder) {
                $days_since = (time() - strtotime($last_reminder)) / DAY_IN_SECONDS;
                if ($days_since < $criteria['inactive_days']) {
                    $results['skipped']++;
                    continue;
                }
            }
            
            // Send reminder
            if (self::send_jury_reminder($jury_member->ID)) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }
        
        return $results;
    }
} 