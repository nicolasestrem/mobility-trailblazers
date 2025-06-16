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
        $assigned_candidate_ids = get_post_meta($jury_member_id, 'assigned_candidates', true);
        if (!is_array($assigned_candidate_ids) || empty($assigned_candidate_ids)) {
            return array();
        }
        
        // Get the actual post objects
        $candidates = array();
        foreach ($assigned_candidate_ids as $candidate_id) {
            $post = get_post($candidate_id);
            if ($post && $post->post_type === 'mt_candidate' && $post->post_status === 'publish') {
                $candidates[] = $post;
            }
        }
        
        return $candidates;
    }
} 