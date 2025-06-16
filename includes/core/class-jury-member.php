<?php
namespace MobilityTrailblazers\Core;

class JuryMember {
    /**
     * Check if user is a jury member
     */
    public function is_jury_member($user_id) {
        return user_can($user_id, 'mt_jury_member');
    }

    /**
     * Get jury member ID for user
     */
    public function get_jury_member_id_for_user($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'mt_jury' 
            AND post_author = %d 
            AND post_status = 'publish'",
            $user_id
        ));
    }

    /**
     * Get assigned candidates for a jury member
     */
    public function get_assigned_candidates($jury_member_id) {
        $assigned_candidates = get_post_meta($jury_member_id, 'assigned_candidates', true);
        return is_array($assigned_candidates) ? $assigned_candidates : array();
    }
} 