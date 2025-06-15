<?php
/**
 * Candidate Post Type
 *
 * @package MobilityTrailblazers
 * @subpackage Core\PostTypes
 */

namespace MobilityTrailblazers\Core\PostTypes;

use MobilityTrailblazers\Core\Abstracts\Abstract_Post_Type;

/**
 * Candidate post type class
 */
class Candidate_Post_Type extends Abstract_Post_Type {
    
    /**
     * Get the post type key
     *
     * @return string
     */
    protected function get_post_type() {
        return 'mt_candidate';
    }
    
    /**
     * Get the post type arguments
     *
     * @return array
     */
    protected function get_args() {
        $args = $this->get_default_args();
        
        return array_merge($args, array(
            'labels' => $this->get_labels(),
            'description' => __('Award candidates', 'mobility-trailblazers'),
            'menu_icon' => 'dashicons-businessman',
            'menu_position' => 25,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'candidates'),
            'capability_type' => array('candidate', 'candidates'),
            'map_meta_cap' => true,
        ));
    }
    
    /**
     * Get post type labels
     *
     * @return array
     */
    private function get_labels() {
        return array(
            'name' => __('Candidates', 'mobility-trailblazers'),
            'singular_name' => __('Candidate', 'mobility-trailblazers'),
            'menu_name' => __('Candidates', 'mobility-trailblazers'),
            'name_admin_bar' => __('Candidate', 'mobility-trailblazers'),
            'add_new' => __('Add New', 'mobility-trailblazers'),
            'add_new_item' => __('Add New Candidate', 'mobility-trailblazers'),
            'new_item' => __('New Candidate', 'mobility-trailblazers'),
            'edit_item' => __('Edit Candidate', 'mobility-trailblazers'),
            'view_item' => __('View Candidate', 'mobility-trailblazers'),
            'all_items' => __('All Candidates', 'mobility-trailblazers'),
            'search_items' => __('Search Candidates', 'mobility-trailblazers'),
            'parent_item_colon' => __('Parent Candidates:', 'mobility-trailblazers'),
            'not_found' => __('No candidates found.', 'mobility-trailblazers'),
            'not_found_in_trash' => __('No candidates found in Trash.', 'mobility-trailblazers'),
            'featured_image' => __('Candidate Image', 'mobility-trailblazers'),
            'set_featured_image' => __('Set candidate image', 'mobility-trailblazers'),
            'remove_featured_image' => __('Remove candidate image', 'mobility-trailblazers'),
            'use_featured_image' => __('Use as candidate image', 'mobility-trailblazers'),
            'archives' => __('Candidate archives', 'mobility-trailblazers'),
            'insert_into_item' => __('Insert into candidate', 'mobility-trailblazers'),
            'uploaded_to_this_item' => __('Uploaded to this candidate', 'mobility-trailblazers'),
            'filter_items_list' => __('Filter candidates list', 'mobility-trailblazers'),
            'items_list_navigation' => __('Candidates list navigation', 'mobility-trailblazers'),
            'items_list' => __('Candidates list', 'mobility-trailblazers'),
        );
    }
    
    /**
     * Initialize hooks specific to candidates
     *
     * @return void
     */
    protected function init_hooks() {
        add_action('add_meta_boxes', array($this, 'add_candidate_meta_boxes'));
        add_action('save_post_mt_candidate', array($this, 'save_candidate_meta'));
        add_filter('manage_mt_candidate_posts_columns', array($this, 'add_candidate_columns'));
        add_action('manage_mt_candidate_posts_custom_column', array($this, 'populate_candidate_columns'), 10, 2);
    }
    
    /**
     * Add meta boxes for candidates
     *
     * @return void
     */
    public function add_candidate_meta_boxes() {
        add_meta_box(
            'candidate_details',
            __('Candidate Details', 'mobility-trailblazers'),
            array($this, 'render_candidate_details_meta_box'),
            'mt_candidate',
            'normal',
            'high'
        );
        
        add_meta_box(
            'candidate_evaluation',
            __('Evaluation Status', 'mobility-trailblazers'),
            array($this, 'render_evaluation_status_meta_box'),
            'mt_candidate',
            'side',
            'default'
        );
    }
    
    /**
     * Render candidate details meta box
     *
     * @param \WP_Post $post The post object
     * @return void
     */
    public function render_candidate_details_meta_box($post) {
        wp_nonce_field('candidate_meta_nonce', 'candidate_meta_nonce');
        
        $company = get_post_meta($post->ID, '_mt_candidate_company', true);
        $position = get_post_meta($post->ID, '_mt_candidate_position', true);
        $email = get_post_meta($post->ID, '_mt_candidate_email', true);
        $website = get_post_meta($post->ID, '_mt_candidate_website', true);
        $linkedin = get_post_meta($post->ID, '_mt_candidate_linkedin', true);
        $innovation_description = get_post_meta($post->ID, '_mt_candidate_innovation', true);
        
        include MT_PLUGIN_DIR . 'admin/meta-boxes/candidate-details.php';
    }
    
    /**
     * Render evaluation status meta box
     *
     * @param \WP_Post $post The post object
     * @return void
     */
    public function render_evaluation_status_meta_box($post) {
        // Get evaluation statistics for this candidate
        global $wpdb;
        
        $total_evaluations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE candidate_id = %d",
            $post->ID
        ));
        
        $average_score = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(total_score) FROM {$wpdb->prefix}mt_candidate_scores WHERE candidate_id = %d",
            $post->ID
        ));
        
        include MT_PLUGIN_DIR . 'admin/meta-boxes/evaluation-status.php';
    }
    
    /**
     * Save candidate meta data
     *
     * @param int $post_id The post ID
     * @return void
     */
    public function save_candidate_meta($post_id) {
        if (!isset($_POST['candidate_meta_nonce']) || !wp_verify_nonce($_POST['candidate_meta_nonce'], 'candidate_meta_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array(
            '_mt_candidate_company',
            '_mt_candidate_position',
            '_mt_candidate_email',
            '_mt_candidate_website',
            '_mt_candidate_linkedin',
            '_mt_candidate_innovation'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
    
    /**
     * Add custom columns to candidates list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_candidate_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['company'] = __('Company', 'mobility-trailblazers');
                $new_columns['category'] = __('Category', 'mobility-trailblazers');
                $new_columns['evaluations'] = __('Evaluations', 'mobility-trailblazers');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate custom columns
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     * @return void
     */
    public function populate_candidate_columns($column, $post_id) {
        switch ($column) {
            case 'company':
                echo esc_html(get_post_meta($post_id, '_mt_candidate_company', true));
                break;
                
            case 'category':
                $terms = get_the_terms($post_id, 'mt_category');
                if ($terms && !is_wp_error($terms)) {
                    $term_names = wp_list_pluck($terms, 'name');
                    echo esc_html(implode(', ', $term_names));
                }
                break;
                
            case 'evaluations':
                global $wpdb;
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE candidate_id = %d",
                    $post_id
                ));
                echo intval($count);
                break;
        }
    }
} 