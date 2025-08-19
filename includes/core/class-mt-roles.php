<?php
/**
 * Roles and Capabilities Management
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Roles
 *
 * Manages user roles and capabilities
 */
class MT_Roles {
    
    /**
     * Add custom roles
     *
     * @return void
     */
    public function add_roles() {
        add_role('mt_jury_member', __('Jury Member', 'mobility-trailblazers'), [
            'read' => true,
            'mt_submit_evaluations' => true,
            'mt_view_assigned_candidates' => true,
            'upload_files' => true
        ]);
        
        add_role('mt_jury_admin', __('Jury Admin', 'mobility-trailblazers'), [
            'read' => true,
            'mt_view_all_evaluations' => true,
            'mt_manage_assignments' => true,
            'mt_view_reports' => true,
            'mt_export_data' => true,
            'upload_files' => true
        ]);
    }
    
    /**
     * Add capabilities to existing roles
     *
     * @return void
     */
    public function add_capabilities() {
        // Administrator capabilities
        $admin_caps = [
            // Candidate capabilities
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
            
            // Jury Member capabilities
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
            
            // Custom capabilities
            'mt_manage_evaluations',
            'mt_submit_evaluations',
            'mt_view_all_evaluations',
            'mt_manage_assignments',
            'mt_manage_settings',
            'mt_export_data',
            'mt_import_data',
            'mt_view_reports',
            'mt_jury_admin'
        ];
        
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($admin_caps as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Editor capabilities
        $editor_caps = [
            // Candidate capabilities
            'edit_mt_candidates',
            'edit_others_mt_candidates',
            'publish_mt_candidates',
            'read_private_mt_candidates',
            'delete_mt_candidates',
            'delete_published_mt_candidates',
            'edit_published_mt_candidates',
            
            // Jury Member capabilities (view only)
            'read_private_mt_jury_members',
            
            // Custom capabilities
            'mt_view_all_evaluations',
            'mt_view_reports'
        ];
        
        $editor_role = get_role('editor');
        if ($editor_role) {
            foreach ($editor_caps as $cap) {
                $editor_role->add_cap($cap);
            }
        }
        
        // Jury member capabilities (ensure they're set)
        $jury_role = get_role('mt_jury_member');
        if ($jury_role) {
            $jury_caps = [
                'read',
                'mt_submit_evaluations',
                'mt_view_assigned_candidates',
                'upload_files'
            ];
            
            foreach ($jury_caps as $cap) {
                $jury_role->add_cap($cap);
            }
        }
        
        // Jury admin capabilities (ensure they're set)
        $jury_admin_role = get_role('mt_jury_admin');
        if ($jury_admin_role) {
            $jury_admin_caps = [
                'read',
                'mt_view_all_evaluations',
                'mt_manage_assignments',
                'mt_view_reports',
                'mt_export_data',
                'upload_files'
            ];
            
            foreach ($jury_admin_caps as $cap) {
                $jury_admin_role->add_cap($cap);
            }
        }
    }
    
    /**
     * Remove capabilities from roles
     *
     * @return void
     */
    public function remove_capabilities() {
        $all_caps = [
            // Candidate capabilities
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
            
            // Jury Member capabilities
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
            
            // Custom capabilities
            'mt_manage_evaluations',
            'mt_submit_evaluations',
            'mt_view_all_evaluations',
            'mt_manage_assignments',
            'mt_manage_settings',
            'mt_export_data',
            'mt_import_data',
            'mt_view_reports',
            'mt_view_assigned_candidates',
            'mt_jury_admin'
        ];
        
        $roles = ['administrator', 'editor', 'mt_jury_member', 'mt_jury_admin'];
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($all_caps as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
} 
