<?php
/**
 * Custom Post Types Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Post_Types
 * Handles registration of custom post types
 */
class MT_Post_Types {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register post types on init hook with very early priority
        add_action('init', array($this, 'register_post_types'), 0);
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        $this->register_candidate_post_type();
        $this->register_jury_post_type();
        $this->register_backup_post_type();
    }
    
    /**
     * Register Candidate Post Type
     */
    private function register_candidate_post_type() {
        $labels = array(
            'name' => __('Candidates', 'mobility-trailblazers'),
            'singular_name' => __('Candidate', 'mobility-trailblazers'),
            'add_new' => __('Add New Candidate', 'mobility-trailblazers'),
            'add_new_item' => __('Add New Candidate', 'mobility-trailblazers'),
            'edit_item' => __('Edit Candidate', 'mobility-trailblazers'),
            'new_item' => __('New Candidate', 'mobility-trailblazers'),
            'view_item' => __('View Candidate', 'mobility-trailblazers'),
            'search_items' => __('Search Candidates', 'mobility-trailblazers'),
            'not_found' => __('No candidates found', 'mobility-trailblazers'),
            'not_found_in_trash' => __('No candidates found in trash', 'mobility-trailblazers')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-groups',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'rewrite' => false,
            'show_in_rest' => true
        );
        
        register_post_type('mt_candidate', $args);
    }
    
    /**
     * Register Jury Member Post Type
     */
    private function register_jury_post_type() {
        $labels = array(
            'name' => __('Jury Members', 'mobility-trailblazers'),
            'singular_name' => __('Jury Member', 'mobility-trailblazers'),
            'add_new' => __('Add New Jury Member', 'mobility-trailblazers'),
            'add_new_item' => __('Add New Jury Member', 'mobility-trailblazers'),
            'edit_item' => __('Edit Jury Member', 'mobility-trailblazers'),
            'new_item' => __('New Jury Member', 'mobility-trailblazers'),
            'view_item' => __('View Jury Member', 'mobility-trailblazers'),
            'search_items' => __('Search Jury Members', 'mobility-trailblazers'),
            'not_found' => __('No jury members found', 'mobility-trailblazers'),
            'not_found_in_trash' => __('No jury members found in trash', 'mobility-trailblazers')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'menu_icon' => 'dashicons-businessman',
            'supports' => array('title', 'thumbnail', 'custom-fields'),
            'show_in_rest' => true
        );
        
        register_post_type('mt_jury', $args);
    }
    
    /**
     * Register Backup Post Type
     */
    private function register_backup_post_type() {
        $labels = array(
            'name' => __('Backups', 'mobility-trailblazers'),
            'singular_name' => __('Backup', 'mobility-trailblazers'),
            'add_new' => __('Create Backup', 'mobility-trailblazers'),
            'add_new_item' => __('Create New Backup', 'mobility-trailblazers'),
            'edit_item' => __('View Backup', 'mobility-trailblazers'),
            'new_item' => __('New Backup', 'mobility-trailblazers'),
            'view_item' => __('View Backup', 'mobility-trailblazers'),
            'search_items' => __('Search Backups', 'mobility-trailblazers'),
            'not_found' => __('No backups found', 'mobility-trailblazers'),
            'not_found_in_trash' => __('No backups found in trash', 'mobility-trailblazers')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => 'do_not_allow',
            ),
            'map_meta_cap' => true,
            'supports' => array('title'),
            'show_in_rest' => false
        );
        
        register_post_type('mt_backup', $args);
    }
} 