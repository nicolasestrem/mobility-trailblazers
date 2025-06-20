<?php
/**
 * Jury Repository
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Repository_Interface;

class MT_Jury_Repository implements MT_Repository_Interface {
    
    /**
     * Find jury member by ID
     */
    public function find($id) {
        $user = get_user_by('id', $id);
        
        if (!$user || !in_array('mt_jury_member', $user->roles)) {
            return null;
        }
        
        return $this->format_jury_member($user);
    }
    
    /**
     * Find all jury members with filters
     */
    public function find_all($args = array()) {
        $defaults = array(
            'role' => 'mt_jury_member',
            'number' => 50,
            'offset' => 0,
            'orderby' => 'display_name',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Add meta query for status if provided
        if (!empty($args['status'])) {
            $args['meta_query'] = array(
                array(
                    'key' => 'mt_jury_status',
                    'value' => $args['status'],
                    'compare' => '='
                )
            );
        }
        
        $users = get_users($args);
        
        $jury_members = array();
        foreach ($users as $user) {
            $jury_members[] = $this->format_jury_member($user);
        }
        
        return $jury_members;
    }
    
    /**
     * Create new jury member
     */
    public function create($data) {
        $userdata = array(
            'user_login' => sanitize_text_field($data['username']),
            'user_email' => sanitize_email($data['email']),
            'user_pass' => $data['password'] ?? wp_generate_password(),
            'display_name' => sanitize_text_field($data['display_name']),
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['last_name'] ?? ''),
            'role' => 'mt_jury_member'
        );
        
        $user_id = wp_insert_user($userdata);
        
        if (is_wp_error($user_id)) {
            return false;
        }
        
        // Add meta data
        $this->update_jury_meta($user_id, $data);
        
        // Send notification if requested
        if (!empty($data['send_notification'])) {
            wp_new_user_notification($user_id, null, 'both');
        }
        
        return $user_id;
    }
    
    /**
     * Update jury member
     */
    public function update($id, $data) {
        $userdata = array('ID' => $id);
        
        // Update basic user data
        if (isset($data['email'])) {
            $userdata['user_email'] = sanitize_email($data['email']);
        }
        
        if (isset($data['display_name'])) {
            $userdata['display_name'] = sanitize_text_field($data['display_name']);
        }
        
        if (isset($data['first_name'])) {
            $userdata['first_name'] = sanitize_text_field($data['first_name']);
        }
        
        if (isset($data['last_name'])) {
            $userdata['last_name'] = sanitize_text_field($data['last_name']);
        }
        
        $result = wp_update_user($userdata);
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // Update meta data
        $this->update_jury_meta($id, $data);
        
        return true;
    }
    
    /**
     * Delete jury member
     */
    public function delete($id) {
        // Remove jury role instead of deleting user
        $user = new \WP_User($id);
        $user->remove_role('mt_jury_member');
        
        // Clean up jury-specific meta
        $meta_keys = array(
            'mt_jury_status',
            'mt_organization',
            'mt_position',
            'mt_expertise',
            'mt_bio',
            'mt_linkedin_url',
            'mt_evaluation_count',
            'mt_last_evaluation'
        );
        
        foreach ($meta_keys as $key) {
            delete_user_meta($id, $key);
        }
        
        return true;
    }
    
    /**
     * Format jury member data
     */
    private function format_jury_member($user) {
        $member = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'registered' => $user->user_registered
        );
        
        // Add meta data
        $meta_fields = array(
            'status' => 'active',
            'organization' => '',
            'position' => '',
            'expertise' => '',
            'bio' => '',
            'linkedin_url' => '',
            'evaluation_count' => 0,
            'last_evaluation' => ''
        );
        
        foreach ($meta_fields as $field => $default) {
            $member[$field] = get_user_meta($user->ID, 'mt_' . $field, true) ?: $default;
        }
        
        // Add avatar URL
        $member['avatar_url'] = get_avatar_url($user->ID);
        
        // Add assignment count
        $assignment_repo = new MT_Assignment_Repository();
        $assignments = $assignment_repo->find_all(array(
            'jury_member_id' => $user->ID,
            'limit' => 1
        ));
        $member['assignment_count'] = count($assignments);
        
        return $member;
    }
    
    /**
     * Update jury member meta data
     */
    private function update_jury_meta($user_id, $data) {
        $meta_fields = array(
            'status',
            'organization',
            'position',
            'expertise',
            'bio',
            'linkedin_url'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($data[$field])) {
                update_user_meta($user_id, 'mt_' . $field, sanitize_text_field($data[$field]));
            }
        }
    }
    
    /**
     * Get active jury members
     */
    public function get_active_members() {
        return $this->find_all(array('status' => 'active'));
    }
    
    /**
     * Get statistics for jury or all juries
     *
     * @param int|null $jury_id
     * @return array|null
     */
    public function get_statistics($jury_id = null) {
        global $wpdb;
        if ($jury_id) {
            // Get statistics for specific jury member
            $user = get_user_by('ID', $jury_id);
            if (!$user) {
                return null;
            }
            $table_name = $wpdb->prefix . 'mt_evaluations';
            $assignments_table = $wpdb->prefix . 'mt_assignments';
            return array(
                'jury_id' => $jury_id,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'total_assignments' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$assignments_table} WHERE jury_member_id = %d",
                    $jury_id
                )),
                'completed_evaluations' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE jury_member_id = %d AND status = 'completed'",
                    $jury_id
                )),
                'draft_evaluations' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE jury_member_id = %d AND status = 'draft'",
                    $jury_id
                )),
                'average_score_given' => $wpdb->get_var($wpdb->prepare(
                    "SELECT AVG(total_score) FROM {$table_name} WHERE jury_member_id = %d AND status = 'completed'",
                    $jury_id
                ))
            );
        } else {
            // Get statistics for all jury members
            $jury_members = get_users(array('role' => 'mt_jury_member'));
            $stats = array();
            foreach ($jury_members as $member) {
                $stats[] = $this->get_statistics($member->ID);
            }
            return $stats;
        }
    }
    
    /**
     * Update evaluation count
     */
    public function increment_evaluation_count($user_id) {
        $current = get_user_meta($user_id, 'mt_evaluation_count', true) ?: 0;
        update_user_meta($user_id, 'mt_evaluation_count', $current + 1);
        update_user_meta($user_id, 'mt_last_evaluation', current_time('mysql'));
    }
}