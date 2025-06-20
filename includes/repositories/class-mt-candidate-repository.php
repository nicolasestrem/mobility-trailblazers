<?php
/**
 * Candidate Repository
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Repository_Interface;

class MT_Candidate_Repository implements MT_Repository_Interface {
    
    /**
     * Find candidate by ID
     */
    public function find($id) {
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'mt_candidate') {
            return null;
        }
        
        return $this->format_candidate($post);
    }
    
    /**
     * Find all candidates with filters
     */
    public function find_all($args = array()) {
        $defaults = array(
            'post_type' => 'mt_candidate',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'offset' => 0,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        $posts = get_posts($args);
        
        $candidates = array();
        foreach ($posts as $post) {
            $candidates[] = $this->format_candidate($post);
        }
        
        return $candidates;
    }
    
    /**
     * Create new candidate
     */
    public function create($data) {
        $post_data = array(
            'post_title' => sanitize_text_field($data['name']),
            'post_content' => wp_kses_post($data['description'] ?? ''),
            'post_type' => 'mt_candidate',
            'post_status' => $data['status'] ?? 'publish',
            'meta_input' => $this->prepare_meta_data($data)
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // Handle categories
        if (!empty($data['categories'])) {
            wp_set_object_terms($post_id, $data['categories'], 'mt_category');
        }
        
        return $post_id;
    }
    
    /**
     * Update candidate
     */
    public function update($id, $data) {
        $post_data = array(
            'ID' => $id,
            'post_title' => sanitize_text_field($data['name']),
            'post_content' => wp_kses_post($data['description'] ?? '')
        );
        
        if (isset($data['status'])) {
            $post_data['post_status'] = $data['status'];
        }
        
        $result = wp_update_post($post_data);
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // Update meta data
        foreach ($this->prepare_meta_data($data) as $key => $value) {
            update_post_meta($id, $key, $value);
        }
        
        // Update categories
        if (isset($data['categories'])) {
            wp_set_object_terms($id, $data['categories'], 'mt_category');
        }
        
        return true;
    }
    
    /**
     * Delete candidate
     */
    public function delete($id) {
        return wp_delete_post($id, true);
    }
    
    /**
     * Format candidate data
     */
    private function format_candidate($post) {
        $candidate = array(
            'id' => $post->ID,
            'name' => $post->post_title,
            'description' => $post->post_content,
            'status' => $post->post_status,
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified
        );
        
        // Add meta data
        $meta_fields = array(
            'organization',
            'website',
            'email',
            'phone',
            'linkedin',
            'innovation_title',
            'innovation_description',
            'impact_description',
            'team_size',
            'founded_year',
            'total_score',
            'evaluation_count'
        );
        
        foreach ($meta_fields as $field) {
            $candidate[$field] = get_post_meta($post->ID, 'mt_' . $field, true);
        }
        
        // Add categories
        $categories = wp_get_object_terms($post->ID, 'mt_category', array('fields' => 'names'));
        $candidate['categories'] = $categories;
        
        // Add featured image
        $candidate['featured_image'] = get_the_post_thumbnail_url($post->ID, 'full');
        
        return $candidate;
    }
    
    /**
     * Prepare meta data for saving
     */
    private function prepare_meta_data($data) {
        $meta = array();
        
        $fields = array(
            'organization',
            'website',
            'email',
            'phone',
            'linkedin',
            'innovation_title',
            'innovation_description',
            'impact_description',
            'team_size',
            'founded_year'
        );
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $meta['mt_' . $field] = sanitize_text_field($data[$field]);
            }
        }
        
        return $meta;
    }
    
    /**
     * Get candidates by category
     */
    public function get_by_category($category_id) {
        $args = array(
            'tax_query' => array(
                array(
                    'taxonomy' => 'mt_category',
                    'field' => 'term_id',
                    'terms' => $category_id
                )
            )
        );
        
        return $this->find_all($args);
    }
    
    /**
     * Search candidates
     */
    public function search($search_term) {
        $args = array(
            's' => $search_term,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'mt_organization',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'mt_innovation_title',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                )
            )
        );
        
        return $this->find_all($args);
    }
    
    /**
     * Get candidate statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total candidates
        $stats['total'] = wp_count_posts('mt_candidate')->publish;
        
        // By category
        $categories = get_terms(array(
            'taxonomy' => 'mt_category',
            'hide_empty' => false
        ));
        
        foreach ($categories as $category) {
            $stats['by_category'][$category->name] = $category->count;
        }
        
        // Average scores
        $stats['avg_score'] = $wpdb->get_var(
            "SELECT AVG(CAST(meta_value AS DECIMAL(10,2))) 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = 'mt_total_score' 
             AND meta_value != ''"
        );
        
        return $stats;
    }
}