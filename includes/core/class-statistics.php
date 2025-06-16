<?php
namespace MobilityTrailblazers\Core;

class Statistics {
    /**
     * Get overview statistics
     */
    public function get_overview_stats() {
        global $wpdb;
        
        return array(
            'total_candidates' => wp_count_posts('mt_candidate')->publish,
            'total_jury_members' => wp_count_posts('mt_jury')->publish,
            'total_votes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes"),
            'total_categories' => wp_count_terms('mt_category')
        );
    }

    /**
     * Get jury member statistics
     */
    public function get_jury_member_stats($user_id) {
        global $wpdb;
        
        $jury_member_id = $this->get_jury_member_id_for_user($user_id);
        if (!$jury_member_id) {
            return array();
        }
        
        return array(
            'total_votes' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE jury_member_id = %d",
                $jury_member_id
            )),
            'assigned_candidates' => count($this->get_assigned_candidates($jury_member_id)),
            'average_score' => $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(score) FROM {$wpdb->prefix}mt_votes WHERE jury_member_id = %d",
                $jury_member_id
            ))
        );
    }

    /**
     * Get top candidates
     */
    public function get_top_candidates($limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT candidate_id, AVG(score) as avg_score, COUNT(*) as vote_count
            FROM {$wpdb->prefix}mt_votes
            GROUP BY candidate_id
            ORDER BY avg_score DESC
            LIMIT %d",
            $limit
        ));
    }

    /**
     * Get evaluation progress
     */
    public function get_evaluation_progress() {
        global $wpdb;
        
        $total_candidates = wp_count_posts('mt_candidate')->publish;
        $total_jury = wp_count_posts('mt_jury')->publish;
        $total_possible_votes = $total_candidates * $total_jury;
        
        $total_votes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes");
        
        return array(
            'total_possible' => $total_possible_votes,
            'total_completed' => $total_votes,
            'percentage' => $total_possible_votes > 0 ? 
                round(($total_votes / $total_possible_votes) * 100, 2) : 0
        );
    }

    /**
     * Helper method to get jury member ID
     */
    private function get_jury_member_id_for_user($user_id) {
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
     * Helper method to get assigned candidates
     */
    private function get_assigned_candidates($jury_member_id) {
        $assigned_candidates = get_post_meta($jury_member_id, 'assigned_candidates', true);
        return is_array($assigned_candidates) ? $assigned_candidates : array();
    }
} 