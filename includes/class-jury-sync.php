<?php
/**
 * Jury Synchronization Handler
 *
 * @package MobilityTrailblazers
 * @since 2.1.0
 */

namespace MobilityTrailblazers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class JurySync
 * Handles synchronization between users with mt_jury_member role and jury posts
 */
class JurySync {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the class
     */
    private function __construct() {
        // Hook into user role changes
        add_action('set_user_role', array($this, 'handle_user_role_change'), 10, 3);
        add_action('add_user_role', array($this, 'handle_user_role_add'), 10, 2);
        add_action('remove_user_role', array($this, 'handle_user_role_remove'), 10, 2);
        
        // Hook into jury post actions
        add_action('before_delete_post', array($this, 'handle_jury_post_delete'));
        add_action('wp_trash_post', array($this, 'handle_jury_post_trash'));
        add_action('untrash_post', array($this, 'handle_jury_post_untrash'));
        add_action('save_post_mt_jury', array($this, 'handle_jury_post_save'), 10, 3);
        
        // Hook into user deletion
        add_action('delete_user', array($this, 'handle_user_delete'));
        
        // Add admin actions
        add_action('admin_init', array($this, 'maybe_sync_all_jury_members'));
        add_action('wp_ajax_mt_sync_jury_members', array($this, 'ajax_sync_jury_members'));
        
        // Add sync check to diagnostic page
        add_action('mt_diagnostic_checks', array($this, 'add_diagnostic_check'));
    }
    
    /**
     * Handle user role change
     */
    public function handle_user_role_change($user_id, $role, $old_roles) {
        if ($role === 'mt_jury_member') {
            $this->create_jury_post_for_user($user_id);
        }
        
        if (in_array('mt_jury_member', $old_roles) && $role !== 'mt_jury_member') {
            $this->handle_jury_role_removal($user_id);
        }
    }
    
    /**
     * Handle user role addition
     */
    public function handle_user_role_add($user_id, $role) {
        if ($role === 'mt_jury_member') {
            $this->create_jury_post_for_user($user_id);
        }
    }
    
    /**
     * Handle user role removal
     */
    public function handle_user_role_remove($user_id, $role) {
        if ($role === 'mt_jury_member') {
            if (!Roles::is_jury_member($user_id)) {
                $this->handle_jury_role_removal($user_id);
            }
        }
    }
    
    /**
     * Create jury post for user
     */
    public function create_jury_post_for_user($user_id) {
        $existing_jury = $this->get_jury_post_for_user($user_id);
        if ($existing_jury) {
            if (get_post_status($existing_jury) === 'trash') {
                wp_untrash_post($existing_jury);
            }
            return $existing_jury;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $jury_post_data = array(
            'post_type' => 'mt_jury',
            'post_title' => $user->display_name,
            'post_status' => 'publish',
            'post_author' => get_current_user_id() ?: 1,
            'meta_input' => array(
                '_mt_jury_user_id' => $user_id,
                '_mt_jury_email' => $user->user_email,
                '_mt_jury_login' => $user->user_login,
                '_mt_jury_sync_created' => current_time('mysql'),
                '_mt_jury_auto_created' => 1
            )
        );
        
        $jury_post_id = wp_insert_post($jury_post_data);
        
        if ($jury_post_id && !is_wp_error($jury_post_id)) {
            do_action('mt_jury_post_created', $jury_post_id, $user_id);
            return $jury_post_id;
        }
        
        return false;
    }
    
    /**
     * Handle jury role removal
     */
    private function handle_jury_role_removal($user_id) {
        $jury_post_id = $this->get_jury_post_for_user($user_id);
        if ($jury_post_id) {
            wp_trash_post($jury_post_id);
            do_action('mt_jury_post_deactivated', $jury_post_id, $user_id);
        }
    }
    
    /**
     * Handle jury post deletion
     */
    public function handle_jury_post_delete($post_id) {
        if (get_post_type($post_id) !== 'mt_jury') {
            return;
        }
        
        $user_id = get_post_meta($post_id, '_mt_jury_user_id', true);
        if ($user_id) {
            $user = get_user_by('id', $user_id);
            if ($user && in_array('mt_jury_member', $user->roles)) {
                $user->remove_role('mt_jury_member');
            }
        }
    }
    
    /**
     * Handle jury post trash
     */
    public function handle_jury_post_trash($post_id) {
        if (get_post_type($post_id) !== 'mt_jury') {
            return;
        }
        
        $user_id = get_post_meta($post_id, '_mt_jury_user_id', true);
        if ($user_id) {
            $user = get_user_by('id', $user_id);
            if ($user && in_array('mt_jury_member', $user->roles)) {
                $user->remove_role('mt_jury_member');
            }
        }
    }
    
    /**
     * Handle jury post untrash
     */
    public function handle_jury_post_untrash($post_id) {
        if (get_post_type($post_id) !== 'mt_jury') {
            return;
        }
        
        $user_id = get_post_meta($post_id, '_mt_jury_user_id', true);
        if ($user_id) {
            $user = get_user_by('id', $user_id);
            if ($user && !in_array('mt_jury_member', $user->roles)) {
                $user->add_role('mt_jury_member');
            }
        }
    }
    
    /**
     * Handle jury post save
     */
    public function handle_jury_post_save($post_id, $post, $update) {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        $user_id = get_post_meta($post_id, '_mt_jury_user_id', true);
        
        if (!$user_id) {
            return;
        }
        
        if ($post->post_status === 'publish') {
            $user = get_user_by('id', $user_id);
            if ($user && !in_array('mt_jury_member', $user->roles)) {
                $user->add_role('mt_jury_member');
            }
        }
    }
    
    /**
     * Handle user deletion
     */
    public function handle_user_delete($user_id) {
        $jury_post_id = $this->get_jury_post_for_user($user_id);
        if ($jury_post_id) {
            wp_delete_post($jury_post_id, true);
        }
    }
    
    /**
     * Get jury post for user
     */
    public function get_jury_post_for_user($user_id) {
        $jury_posts = get_posts(array(
            'post_type' => 'mt_jury',
            'post_status' => array('publish', 'draft', 'trash'),
            'meta_query' => array(
                array(
                    'key' => '_mt_jury_user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'fields' => 'ids'
        ));
        
        return !empty($jury_posts) ? $jury_posts[0] : false;
    }
    
    /**
     * Sync all jury members
     */
    public function sync_all_jury_members() {
        $synced = 0;
        $errors = 0;
        
        $jury_users = get_users(array(
            'role' => 'mt_jury_member',
            'fields' => 'ID'
        ));
        
        foreach ($jury_users as $user_id) {
            $result = $this->create_jury_post_for_user($user_id);
            if ($result) {
                $synced++;
            } else {
                $errors++;
            }
        }
        
        $jury_posts = get_posts(array(
            'post_type' => 'mt_jury',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($jury_posts as $jury_post_id) {
            $user_id = get_post_meta($jury_post_id, '_mt_jury_user_id', true);
            if ($user_id) {
                $user = get_user_by('id', $user_id);
                if (!$user || !in_array('mt_jury_member', $user->roles)) {
                    wp_trash_post($jury_post_id);
                }
            }
        }
        
        return array(
            'synced' => $synced,
            'errors' => $errors
        );
    }
    
    /**
     * Maybe sync all jury members on admin init
     */
    public function maybe_sync_all_jury_members() {
        if (isset($_GET['mt_sync_jury']) && current_user_can('manage_options')) {
            $result = $this->sync_all_jury_members();
            
            $message = sprintf(
                'Jury sync completed: %d synced, %d errors',
                $result['synced'],
                $result['errors']
            );
            
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
            });
        }
    }
    
    /**
     * AJAX handler for jury sync
     */
    public function ajax_sync_jury_members() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Check nonce
        check_ajax_referer('mt_sync_jury_nonce', 'nonce');
        
        $result = $this->sync_all_jury_members();
        
        wp_send_json_success($result);
    }
    
    /**
     * Add diagnostic check
     */
    public function add_diagnostic_check() {
        echo '<div class="mt-diagnostic-section">';
        echo '<h2>Jury Synchronization Status</h2>';
        
        $this->check_jury_sync_status();
        
        echo '<p><a href="' . esc_url(add_query_arg('mt_sync_jury', '1')) . '" class="button button-secondary">Sync All Jury Members</a></p>';
        echo '</div>';
    }
    
    /**
     * Check jury sync status
     */
    public function check_jury_sync_status() {
        echo '<table class="widefat">';
        echo '<thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead>';
        echo '<tbody>';
        
        $jury_users = get_users(array('role' => 'mt_jury_member', 'fields' => 'ID'));
        $users_without_posts = 0;
        
        foreach ($jury_users as $user_id) {
            if (!$this->get_jury_post_for_user($user_id)) {
                $users_without_posts++;
            }
        }
        
        echo '<tr>';
        echo '<td>Users with jury role missing jury posts</td>';
        if ($users_without_posts === 0) {
            echo '<td><span style="color: green;">✓ OK</span></td>';
            echo '<td>All jury users have corresponding posts</td>';
        } else {
            echo '<td><span style="color: red;">✗ Issues Found</span></td>';
            echo '<td>' . esc_html($users_without_posts) . ' users need jury posts</td>';
        }
        echo '</tr>';
        
        $jury_posts = get_posts(array(
            'post_type' => 'mt_jury',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        $orphaned_posts = 0;
        foreach ($jury_posts as $jury_post_id) {
            $user_id = get_post_meta($jury_post_id, '_mt_jury_user_id', true);
            if ($user_id) {
                $user = get_user_by('id', $user_id);
                if (!$user || !in_array('mt_jury_member', $user->roles)) {
                    $orphaned_posts++;
                }
            }
        }
        
        echo '<tr>';
        echo '<td>Orphaned jury posts</td>';
        if ($orphaned_posts === 0) {
            echo '<td><span style="color: green;">✓ OK</span></td>';
            echo '<td>All jury posts have valid users</td>';
        } else {
            echo '<td><span style="color: orange;">⚠ Issues Found</span></td>';
            echo '<td>' . esc_html($orphaned_posts) . ' posts need cleanup</td>';
        }
        echo '</tr>';
        
        echo '<tr>';
        echo '<td><strong>Total jury users</strong></td>';
        echo '<td>' . count($jury_users) . '</td>';
        echo '<td>Users with mt_jury_member role</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<td><strong>Total jury posts</strong></td>';
        echo '<td>' . count($jury_posts) . '</td>';
        echo '<td>Published jury posts</td>';
        echo '</tr>';
        
        echo '</tbody></table>';
    }
    
    /**
     * Log action
     */
    private function log_action($action, $message) {
        if (function_exists('mt_log_action')) {
            mt_log_action($action, $message);
        } else {
            error_log("MT Jury Sync: {$action} - {$message}");
        }
    }
} 