<?php
namespace MobilityTrailblazers\Core;

class Candidate {
    /**
     * Get candidate data
     */
    public function get_candidate($candidate_id) {
        $post = get_post($candidate_id);
        if (!$post || $post->post_type !== 'mt_candidate') {
            return null;
        }

        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'categories' => wp_get_post_terms($post->ID, 'mt_category', array('fields' => 'names')),
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'full'),
            'meta' => array(
                'company' => get_post_meta($post->ID, 'company', true),
                'website' => get_post_meta($post->ID, 'website', true),
                'contact_email' => get_post_meta($post->ID, 'contact_email', true),
                'contact_phone' => get_post_meta($post->ID, 'contact_phone', true)
            )
        );
    }

    /**
     * Get all candidates
     */
    public function get_all_candidates($args = array()) {
        $defaults = array(
            'post_type' => 'mt_candidate',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $defaults);
        $query = new \WP_Query($args);
        
        $candidates = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $candidates[] = $this->get_candidate(get_the_ID());
            }
        }
        wp_reset_postdata();

        return $candidates;
    }

    /**
     * Get candidates by category
     */
    public function get_candidates_by_category($category_slug) {
        return $this->get_all_candidates(array(
            'tax_query' => array(
                array(
                    'taxonomy' => 'mt_category',
                    'field' => 'slug',
                    'terms' => $category_slug
                )
            )
        ));
    }

    /**
     * Get candidate scores
     */
    public function get_candidate_scores($candidate_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT AVG(score) as avg_score, COUNT(*) as vote_count
            FROM {$wpdb->prefix}mt_votes
            WHERE candidate_id = %d
            GROUP BY candidate_id",
            $candidate_id
        ));
    }

    /**
     * Get candidate evaluations
     */
    public function get_candidate_evaluations($candidate_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, u.display_name as jury_name
            FROM {$wpdb->prefix}mt_votes v
            JOIN {$wpdb->users} u ON v.jury_member_id = u.ID
            WHERE v.candidate_id = %d
            ORDER BY v.created_at DESC",
            $candidate_id
        ));
    }
} 