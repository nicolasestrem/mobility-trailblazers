<?php
/**
 * Roles and Capabilities management class
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Roles
 * Handles user roles and capabilities
 */
class MT_Roles {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add custom capabilities to administrator on init
        add_action('admin_init', array($this, 'add_admin_capabilities'));
        
        // Filter user capabilities
        add_filter('user_has_cap', array($this, 'filter_user_capabilities'), 10, 4);
    }
    
    /**
     * Create custom roles
     */
    public static function create_roles() {
        // MT Award Admin role
        add_role(
            'mt_award_admin',
            __('MT Award Admin', 'mobility-trailblazers'),
            array(
                // WordPress capabilities
                'read' => true,
                'upload_files' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                
                // Candidate capabilities
                'edit_mt_candidate' => true,
                'read_mt_candidate' => true,
                'delete_mt_candidate' => true,
                'edit_mt_candidates' => true,
                'edit_others_mt_candidates' => true,
                'publish_mt_candidates' => true,
                'read_private_mt_candidates' => true,
                'delete_mt_candidates' => true,
                'delete_private_mt_candidates' => true,
                'delete_published_mt_candidates' => true,
                'delete_others_mt_candidates' => true,
                'edit_private_mt_candidates' => true,
                'edit_published_mt_candidates' => true,
                
                // Jury capabilities
                'edit_mt_jury' => true,
                'read_mt_jury' => true,
                'delete_mt_jury' => true,
                'edit_mt_jurys' => true,
                'edit_others_mt_jurys' => true,
                'publish_mt_jurys' => true,
                'read_private_mt_jurys' => true,
                'delete_mt_jurys' => true,
                'delete_private_mt_jurys' => true,
                'delete_published_mt_jurys' => true,
                'delete_others_mt_jurys' => true,
                'edit_private_mt_jurys' => true,
                'edit_published_mt_jurys' => true,
                
                // Backup capabilities
                'edit_mt_backup' => true,
                'read_mt_backup' => true,
                'delete_mt_backup' => true,
                'edit_mt_backups' => true,
                'edit_others_mt_backups' => true,
                'publish_mt_backups' => true,
                'read_private_mt_backups' => true,
                'delete_mt_backups' => true,
                'delete_private_mt_backups' => true,
                'delete_published_mt_backups' => true,
                'delete_others_mt_backups' => true,
                'edit_private_mt_backups' => true,
                'edit_published_mt_backups' => true,
                
                // Custom capabilities
                'mt_manage_awards' => true,
                'mt_manage_assignments' => true,
                'mt_view_all_evaluations' => true,
                'mt_manage_voting' => true,
                'mt_export_data' => true,
                'mt_manage_jury_members' => true,
                'mt_reset_votes' => true,
                'mt_create_backups' => true,
                'mt_restore_backups' => true,
            )
        );
        
        // MT Jury Member role
        add_role(
            'mt_jury_member',
            __('MT Jury Member', 'mobility-trailblazers'),
            array(
                // WordPress capabilities
                'read' => true,
                'upload_files' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                
                // Candidate capabilities (read only)
                'read_mt_candidate' => true,
                'read_private_mt_candidates' => true,
                
                // Jury capabilities (can edit own profile)
                'read_mt_jury' => true,
                'edit_mt_jury' => true,
                
                // Custom capabilities
                'mt_submit_evaluations' => true,
                'mt_view_candidates' => true,
                'mt_access_jury_dashboard' => true,
                'mt_export_own_evaluations' => true,
            )
        );
    }
    
    /**
     * Remove custom roles
     */
    public static function remove_roles() {
        remove_role('mt_award_admin');
        remove_role('mt_jury_member');
    }
    
    /**
     * Add capabilities to administrator role
     */
    public function add_admin_capabilities() {
        $admin_role = get_role('administrator');
        
        if (!$admin_role) {
            return;
        }
        
        // Candidate capabilities
        $admin_role->add_cap('edit_mt_candidate');
        $admin_role->add_cap('read_mt_candidate');
        $admin_role->add_cap('delete_mt_candidate');
        $admin_role->add_cap('edit_mt_candidates');
        $admin_role->add_cap('edit_others_mt_candidates');
        $admin_role->add_cap('publish_mt_candidates');
        $admin_role->add_cap('read_private_mt_candidates');
        $admin_role->add_cap('delete_mt_candidates');
        $admin_role->add_cap('delete_private_mt_candidates');
        $admin_role->add_cap('delete_published_mt_candidates');
        $admin_role->add_cap('delete_others_mt_candidates');
        $admin_role->add_cap('edit_private_mt_candidates');
        $admin_role->add_cap('edit_published_mt_candidates');
        
        // Jury capabilities
        $admin_role->add_cap('edit_mt_jury');
        $admin_role->add_cap('read_mt_jury');
        $admin_role->add_cap('delete_mt_jury');
        $admin_role->add_cap('edit_mt_jurys');
        $admin_role->add_cap('edit_others_mt_jurys');
        $admin_role->add_cap('publish_mt_jurys');
        $admin_role->add_cap('read_private_mt_jurys');
        $admin_role->add_cap('delete_mt_jurys');
        $admin_role->add_cap('delete_private_mt_jurys');
        $admin_role->add_cap('delete_published_mt_jurys');
        $admin_role->add_cap('delete_others_mt_jurys');
        $admin_role->add_cap('edit_private_mt_jurys');
        $admin_role->add_cap('edit_published_mt_jurys');
        
        // Backup capabilities
        $admin_role->add_cap('edit_mt_backup');
        $admin_role->add_cap('read_mt_backup');
        $admin_role->add_cap('delete_mt_backup');
        $admin_role->add_cap('edit_mt_backups');
        $admin_role->add_cap('edit_others_mt_backups');
        $admin_role->add_cap('publish_mt_backups');
        $admin_role->add_cap('read_private_mt_backups');
        $admin_role->add_cap('delete_mt_backups');
        $admin_role->add_cap('delete_private_mt_backups');
        $admin_role->add_cap('delete_published_mt_backups');
        $admin_role->add_cap('delete_others_mt_backups');
        $admin_role->add_cap('edit_private_mt_backups');
        $admin_role->add_cap('edit_published_mt_backups');
        
        // Custom capabilities
        $admin_role->add_cap('mt_manage_awards');
        $admin_role->add_cap('mt_manage_assignments');
        $admin_role->add_cap('mt_view_all_evaluations');
        $admin_role->add_cap('mt_manage_voting');
        $admin_role->add_cap('mt_export_data');
        $admin_role->add_cap('mt_manage_jury_members');
        $admin_role->add_cap('mt_submit_evaluations');
        $admin_role->add_cap('mt_view_candidates');
        $admin_role->add_cap('mt_access_jury_dashboard');
        $admin_role->add_cap('mt_export_own_evaluations');
        $admin_role->add_cap('mt_reset_votes');
        $admin_role->add_cap('mt_create_backups');
        $admin_role->add_cap('mt_restore_backups');
    }
    
    /**
     * Filter user capabilities dynamically
     *
     * @param array $allcaps All user capabilities
     * @param array $caps Required capabilities
     * @param array $args Arguments
     * @param WP_User $user User object
     * @return array Modified capabilities
     */
    public function filter_user_capabilities($allcaps, $caps, $args, $user) {
        // Allow jury members to edit their own jury profile
        if (isset($args[0]) && $args[0] === 'edit_post' && isset($args[2])) {
            $post = get_post($args[2]);
            
            if ($post && $post->post_type === 'mt_jury') {
                $jury_user_id = get_post_meta($post->ID, '_mt_user_id', true);
                
                if ($jury_user_id && $jury_user_id == $user->ID) {
                    $allcaps['edit_mt_jury'] = true;
                    $allcaps['edit_mt_jurys'] = true;
                    $allcaps['edit_published_mt_jurys'] = true;
                }
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Check if user has specific capability
     *
     * @param string $capability Capability to check
     * @param int $user_id User ID (optional, defaults to current user)
     * @return bool Whether user has capability
     */
    public static function user_can($capability, $user_id = null) {
        if (null === $user_id) {
            return current_user_can($capability);
        }
        
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return false;
        }
        
        return user_can($user, $capability);
    }
    
    /**
     * Get users with specific capability
     *
     * @param string $capability Capability to check
     * @return array Array of user objects
     */
    public static function get_users_with_capability($capability) {
        $users = array();
        
        // Get all roles
        global $wp_roles;
        $roles_with_cap = array();
        
        foreach ($wp_roles->roles as $role_name => $role_info) {
            if (isset($role_info['capabilities'][$capability]) && $role_info['capabilities'][$capability]) {
                $roles_with_cap[] = $role_name;
            }
        }
        
        // Get users with those roles
        if (!empty($roles_with_cap)) {
            $user_query = new WP_User_Query(array(
                'role__in' => $roles_with_cap,
                'orderby' => 'display_name',
                'order' => 'ASC',
            ));
            
            $users = $user_query->get_results();
        }
        
        return $users;
    }
    
    /**
     * Create WordPress user for jury member
     *
     * @param int $jury_member_id Jury member post ID
     * @param array $user_data User data
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    public static function create_jury_user($jury_member_id, $user_data = array()) {
        $jury_member = get_post($jury_member_id);
        
        if (!$jury_member || $jury_member->post_type !== 'mt_jury') {
            return new WP_Error('invalid_jury_member', __('Invalid jury member.', 'mobility-trailblazers'));
        }
        
        // Check if user already exists
        $existing_user_id = get_post_meta($jury_member_id, '_mt_user_id', true);
        if ($existing_user_id && get_user_by('id', $existing_user_id)) {
            return new WP_Error('user_exists', __('User already exists for this jury member.', 'mobility-trailblazers'));
        }
        
        // Get email
        $email = isset($user_data['email']) ? $user_data['email'] : get_post_meta($jury_member_id, '_mt_email', true);
        if (!$email || !is_email($email)) {
            return new WP_Error('invalid_email', __('Valid email address required.', 'mobility-trailblazers'));
        }
        
        // Check if email already exists
        if (email_exists($email)) {
            return new WP_Error('email_exists', __('Email address already registered.', 'mobility-trailblazers'));
        }
        
        // Generate username
        $username = isset($user_data['username']) ? $user_data['username'] : sanitize_user(strtolower(str_replace(' ', '.', $jury_member->post_title)));
        $base_username = $username;
        $counter = 1;
        
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        // Generate password
        $password = isset($user_data['password']) ? $user_data['password'] : wp_generate_password(12, true, false);
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $jury_member->post_title,
            'first_name' => isset($user_data['first_name']) ? $user_data['first_name'] : '',
            'last_name' => isset($user_data['last_name']) ? $user_data['last_name'] : '',
            'role' => 'mt_jury_member',
        ));
        
        // Link user to jury member
        update_post_meta($jury_member_id, '_mt_user_id', $user_id);
        update_user_meta($user_id, '_mt_jury_member_id', $jury_member_id);
        
        // Send notification email
        if (!isset($user_data['send_notification']) || $user_data['send_notification']) {
            self::send_jury_welcome_email($user_id, $password);
        }
        
        return $user_id;
    }
    
    /**
     * Send welcome email to jury member
     *
     * @param int $user_id User ID
     * @param string $password Password
     */
    private static function send_jury_welcome_email($user_id, $password) {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return;
        }
        
        $subject = sprintf(__('Welcome to %s - Jury Member Access', 'mobility-trailblazers'), get_bloginfo('name'));
        
        $message = sprintf(__('Dear %s,', 'mobility-trailblazers'), $user->display_name) . "\n\n";
        $message .= __('You have been added as a jury member for the Mobility Trailblazers Award.', 'mobility-trailblazers') . "\n\n";
        $message .= __('Your login credentials:', 'mobility-trailblazers') . "\n";
        $message .= sprintf(__('Username: %s', 'mobility-trailblazers'), $user->user_login) . "\n";
        $message .= sprintf(__('Password: %s', 'mobility-trailblazers'), $password) . "\n\n";
        $message .= sprintf(__('Login URL: %s', 'mobility-trailblazers'), wp_login_url()) . "\n\n";
        $message .= __('Please change your password after your first login.', 'mobility-trailblazers') . "\n\n";
        $message .= __('Best regards,', 'mobility-trailblazers') . "\n";
        $message .= get_bloginfo('name');
        
        wp_mail($user->user_email, $subject, $message);
    }
} 