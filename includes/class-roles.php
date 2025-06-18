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
     * Add capabilities to administrator role
     * This runs on every admin_init to ensure capabilities are always present
     */
    public function add_admin_capabilities() {
        $admin_role = get_role('administrator');
        
        if (!$admin_role) {
            return;
        }
        
        // Get all MT capabilities
        $capabilities = self::get_all_mt_capabilities();
        
        // Check if we need to add any capabilities
        $needs_update = false;
        foreach ($capabilities as $cap) {
            if (!isset($admin_role->capabilities[$cap]) || !$admin_role->capabilities[$cap]) {
                $needs_update = true;
                break;
            }
        }
        
        // Add capabilities if needed
        if ($needs_update) {
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
            
            // Clear cache
            wp_cache_delete('user_roles', 'options');
        }
    }
    
    /**
     * Get all MT capabilities
     *
     * @return array List of all MT capabilities
     */
    public static function get_all_mt_capabilities() {
        return array(
            // Custom capabilities
            'mt_manage_awards',
            'mt_manage_assignments',
            'mt_view_all_evaluations',
            'mt_manage_voting',
            'mt_export_data',
            'mt_manage_jury_members',
            'mt_submit_evaluations',
            'mt_view_candidates',
            'mt_access_jury_dashboard',
            'mt_export_own_evaluations',
            'mt_reset_votes',
            'mt_create_backups',
            'mt_restore_backups',
            
            // Post type capabilities - Candidates
            'edit_mt_candidate',
            'read_mt_candidate',
            'delete_mt_candidate',
            'edit_mt_candidates',
            'edit_others_mt_candidates',
            'publish_mt_candidates',
            'read_private_mt_candidates',
            'delete_mt_candidates',
            'delete_private_mt_candidates',
            'delete_published_mt_candidates',
            'delete_others_mt_candidates',
            'edit_private_mt_candidates',
            'edit_published_mt_candidates',
            
            // Post type capabilities - Jury Members (both naming conventions)
            'edit_mt_jury_member',
            'read_mt_jury_member',
            'delete_mt_jury_member',
            'edit_mt_jury_members',
            'edit_others_mt_jury_members',
            'publish_mt_jury_members',
            'read_private_mt_jury_members',
            'delete_mt_jury_members',
            'delete_private_mt_jury_members',
            'delete_published_mt_jury_members',
            'delete_others_mt_jury_members',
            'edit_private_mt_jury_members',
            'edit_published_mt_jury_members',
            
            // Alternative naming (mt_jury vs mt_jury_member)
            'edit_mt_jury',
            'read_mt_jury',
            'delete_mt_jury',
            'edit_mt_jurys',
            'edit_others_mt_jurys',
            'publish_mt_jurys',
            'read_private_mt_jurys',
            'delete_mt_jurys',
            'delete_private_mt_jurys',
            'delete_published_mt_jurys',
            'delete_others_mt_jurys',
            'edit_private_mt_jurys',
            'edit_published_mt_jurys',
            
            // Post type capabilities - Backups
            'edit_mt_backup',
            'read_mt_backup',
            'delete_mt_backup',
            'edit_mt_backups',
            'edit_others_mt_backups',
            'publish_mt_backups',
            'read_private_mt_backups',
            'delete_mt_backups',
            'delete_private_mt_backups',
            'delete_published_mt_backups',
            'delete_others_mt_backups',
            'edit_private_mt_backups',
            'edit_published_mt_backups',
        );
    }
    
    /**
     * Create custom roles
     */
    public static function create_roles() {
        // Get administrator role to ensure it has all capabilities
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $capabilities = self::get_all_mt_capabilities();
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
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
                
                // Custom capabilities
                'mt_manage_awards' => true,
                'mt_manage_assignments' => true,
                'mt_view_all_evaluations' => true,
                'mt_manage_voting' => true,
                'mt_export_data' => true,
                'mt_manage_jury_members' => true,
                
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
                
                // Alternative naming
                'edit_mt_jury_member' => true,
                'read_mt_jury_member' => true,
                'delete_mt_jury_member' => true,
                'edit_mt_jury_members' => true,
                'edit_others_mt_jury_members' => true,
                'publish_mt_jury_members' => true,
                'read_private_mt_jury_members' => true,
                'delete_mt_jury_members' => true,
                'delete_private_mt_jury_members' => true,
                'delete_published_mt_jury_members' => true,
                'delete_others_mt_jury_members' => true,
                'edit_private_mt_jury_members' => true,
                'edit_published_mt_jury_members' => true,
            )
        );
        
        // MT Jury Member role
        add_role(
            'mt_jury_member',
            __('MT Jury Member', 'mobility-trailblazers'),
            array(
                // WordPress capabilities
                'read' => true,
                
                // Custom capabilities
                'mt_submit_evaluations' => true,
                'mt_view_candidates' => true,
                'mt_access_jury_dashboard' => true,
                'mt_export_own_evaluations' => true,
                
                // Read-only access to candidates
                'read_mt_candidate' => true,
                'read_private_mt_candidates' => true,
            )
        );
        
        // Clear capabilities cache
        wp_cache_delete('user_roles', 'options');
    }
    
    /**
     * Remove custom roles
     */
    public static function remove_roles() {
        remove_role('mt_award_admin');
        remove_role('mt_jury_member');
    }
    
    /**
     * Filter user capabilities
     *
     * @param array $allcaps All user capabilities
     * @param array $caps Required capabilities
     * @param array $args Arguments
     * @param WP_User $user User object
     * @return array Modified capabilities
     */
    public function filter_user_capabilities($allcaps, $caps, $args, $user) {
        // Check if user is linked to a jury member
        if (in_array('mt_submit_evaluations', $caps)) {
            $jury_member = mt_get_jury_member_by_user_id($user->ID);
            
            if ($jury_member) {
                $allcaps['mt_submit_evaluations'] = true;
                $allcaps['mt_view_candidates'] = true;
                $allcaps['mt_access_jury_dashboard'] = true;
                $allcaps['mt_export_own_evaluations'] = true;
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Fix assignment data types
     *
     * @return int Number of records processed
     */
    public static function fix_assignment_data_types() {
        global $wpdb;
        
        $processed = 0;
        
        // Get all posts with _mt_assigned_jury_members meta
        $posts = $wpdb->get_results(
            "SELECT post_id, meta_value 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_mt_assigned_jury_members'"
        );
        
        foreach ($posts as $post) {
            $value = maybe_unserialize($post->meta_value);
            
            if (is_array($value)) {
                // Convert all values to integers
                $fixed_value = array_map('intval', $value);
                
                // Update the meta value
                update_post_meta($post->post_id, '_mt_assigned_jury_members', $fixed_value);
                $processed++;
            }
        }
        
        return $processed;
    }
} 