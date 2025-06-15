<?php
/**
 * Roles and Capabilities Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
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
     * Create custom roles for the plugin
     */
    public static function create_roles() {
        // Create MT Jury Member role
        add_role('mt_jury_member', 'MT Jury Member', array(
            // Basic WordPress capabilities
            'read' => true,
            'edit_posts' => true,
            'upload_files' => true,
            
            // Custom post type capabilities for candidates
            'read_mt_candidate' => true,
            'read_private_mt_candidates' => true,
            'edit_mt_candidates' => true,
            'edit_published_mt_candidates' => true,
            
            // Jury evaluation capabilities
            'mt_submit_evaluations' => true,
            'mt_view_candidates' => true,
            'mt_access_jury_dashboard' => true,
        ));
        
        // Create MT Award Admin role
        add_role('mt_award_admin', 'MT Award Administrator', array(
            // All admin capabilities
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'upload_files' => true,
            'manage_options' => true,
            
            // All candidate capabilities
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
            
            // All jury capabilities
            'edit_mt_jury' => true,
            'read_mt_jury' => true,
            'delete_mt_jury' => true,
            'edit_mt_jurys' => true,
            'edit_others_mt_jurys' => true,
            'publish_mt_jurys' => true,
            'read_private_mt_jurys' => true,
            'delete_mt_jurys' => true,
            
            // Award management capabilities
            'mt_manage_awards' => true,
            'mt_manage_assignments' => true,
            'mt_view_all_evaluations' => true,
            'mt_export_data' => true,
            'mt_manage_voting' => true,
        ));
        
        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            // Give admins all MT capabilities
            $admin_role->add_cap('mt_submit_evaluations');
            $admin_role->add_cap('mt_view_candidates');
            $admin_role->add_cap('mt_access_jury_dashboard');
            $admin_role->add_cap('mt_manage_awards');
            $admin_role->add_cap('mt_manage_assignments');
            $admin_role->add_cap('mt_view_all_evaluations');
            $admin_role->add_cap('mt_export_data');
            $admin_role->add_cap('mt_manage_voting');
        }
    }
    
    /**
     * Remove custom roles and capabilities
     */
    public static function remove_roles() {
        // Remove custom roles
        remove_role('mt_jury_member');
        remove_role('mt_award_admin');
        
        // Remove custom capabilities from administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('mt_submit_evaluations');
            $admin_role->remove_cap('mt_view_candidates');
            $admin_role->remove_cap('mt_access_jury_dashboard');
            $admin_role->remove_cap('mt_manage_awards');
            $admin_role->remove_cap('mt_manage_assignments');
            $admin_role->remove_cap('mt_view_all_evaluations');
            $admin_role->remove_cap('mt_export_data');
            $admin_role->remove_cap('mt_manage_voting');
        }
    }
    
    /**
     * Check if user is a jury member
     */
    public static function is_jury_member($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Check if user has jury member role or admin capabilities
        return in_array('mt_jury_member', $user->roles) || 
               in_array('mt_award_admin', $user->roles) || 
               in_array('administrator', $user->roles) ||
               user_can($user_id, 'mt_access_jury_dashboard');
    }
} 