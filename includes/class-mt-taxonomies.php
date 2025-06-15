<?php
/**
 * Custom Taxonomies Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Taxonomies
 * Handles registration of custom taxonomies
 */
class MT_Taxonomies {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register taxonomies on init hook with very early priority
        add_action('init', array($this, 'register_taxonomies'), 0);
    }
    
    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        $this->register_category_taxonomy();
        $this->register_phase_taxonomy();
        $this->register_status_taxonomy();
        $this->register_award_year_taxonomy();
    }
    
    /**
     * Register Category taxonomy for candidates
     */
    private function register_category_taxonomy() {
        $labels = array(
            'name' => __('Categories', 'mobility-trailblazers'),
            'singular_name' => __('Category', 'mobility-trailblazers'),
            'search_items' => __('Search Categories', 'mobility-trailblazers'),
            'all_items' => __('All Categories', 'mobility-trailblazers'),
            'edit_item' => __('Edit Category', 'mobility-trailblazers'),
            'update_item' => __('Update Category', 'mobility-trailblazers'),
            'add_new_item' => __('Add New Category', 'mobility-trailblazers'),
            'new_item_name' => __('New Category Name', 'mobility-trailblazers'),
            'menu_name' => __('Categories', 'mobility-trailblazers')
        );
        
        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'mobility-category'),
            'show_in_rest' => true
        );
        
        register_taxonomy('mt_category', 'mt_candidate', $args);
    }
    
    /**
     * Register Phase taxonomy for tracking selection phases
     */
    private function register_phase_taxonomy() {
        $labels = array(
            'name' => __('Phases', 'mobility-trailblazers'),
            'singular_name' => __('Phase', 'mobility-trailblazers'),
            'search_items' => __('Search Phases', 'mobility-trailblazers'),
            'all_items' => __('All Phases', 'mobility-trailblazers'),
            'edit_item' => __('Edit Phase', 'mobility-trailblazers'),
            'update_item' => __('Update Phase', 'mobility-trailblazers'),
            'add_new_item' => __('Add New Phase', 'mobility-trailblazers'),
            'new_item_name' => __('New Phase Name', 'mobility-trailblazers'),
            'menu_name' => __('Phases', 'mobility-trailblazers')
        );
        
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'phase'),
            'show_in_rest' => true
        );
        
        register_taxonomy('mt_phase', 'mt_candidate', $args);
    }
    
    /**
     * Register Status taxonomy for tracking candidate status
     */
    private function register_status_taxonomy() {
        $labels = array(
            'name' => __('Statuses', 'mobility-trailblazers'),
            'singular_name' => __('Status', 'mobility-trailblazers'),
            'search_items' => __('Search Statuses', 'mobility-trailblazers'),
            'all_items' => __('All Statuses', 'mobility-trailblazers'),
            'edit_item' => __('Edit Status', 'mobility-trailblazers'),
            'update_item' => __('Update Status', 'mobility-trailblazers'),
            'add_new_item' => __('Add New Status', 'mobility-trailblazers'),
            'new_item_name' => __('New Status Name', 'mobility-trailblazers'),
            'menu_name' => __('Statuses', 'mobility-trailblazers')
        );
        
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'status'),
            'show_in_rest' => true
        );
        
        register_taxonomy('mt_status', 'mt_candidate', $args);
    }
    
    /**
     * Register Award Year taxonomy
     */
    private function register_award_year_taxonomy() {
        $labels = array(
            'name' => __('Award Years', 'mobility-trailblazers'),
            'singular_name' => __('Award Year', 'mobility-trailblazers'),
            'search_items' => __('Search Years', 'mobility-trailblazers'),
            'all_items' => __('All Years', 'mobility-trailblazers'),
            'edit_item' => __('Edit Year', 'mobility-trailblazers'),
            'update_item' => __('Update Year', 'mobility-trailblazers'),
            'add_new_item' => __('Add New Year', 'mobility-trailblazers'),
            'new_item_name' => __('New Year', 'mobility-trailblazers'),
            'menu_name' => __('Award Years', 'mobility-trailblazers')
        );
        
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'award-year'),
            'show_in_rest' => true
        );
        
        register_taxonomy('mt_award_year', 'mt_candidate', $args);
    }
    
    /**
     * Create default terms
     */
    public static function create_default_terms() {
        // Categories (3 dimensions from documentation)
        $categories = array(
            'established-companies' => __('Established Companies', 'mobility-trailblazers'),
            'startups-new-makers' => __('Start-ups & New Makers', 'mobility-trailblazers'),
            'infrastructure-politics-public' => __('Infrastructure / Politics / Public Companies', 'mobility-trailblazers')
        );

        foreach ($categories as $slug => $name) {
            if (!term_exists($name, 'mt_category')) {
                wp_insert_term($name, 'mt_category', array('slug' => $slug));
            }
        }

        // Selection statuses
        $statuses = array(
            'longlist' => __('Longlist (~200)', 'mobility-trailblazers'),
            'shortlist' => __('Shortlist (50)', 'mobility-trailblazers'),
            'finalist' => __('Finalist (25)', 'mobility-trailblazers'),
            'winner' => __('Winner (Top 3)', 'mobility-trailblazers'),
            'rejected' => __('Rejected', 'mobility-trailblazers')
        );

        foreach ($statuses as $slug => $name) {
            if (!term_exists($name, 'mt_status')) {
                wp_insert_term($name, 'mt_status', array('slug' => $slug));
            }
        }

        // Current award year
        $current_year = date('Y');
        if (!term_exists($current_year, 'mt_award_year')) {
            wp_insert_term($current_year, 'mt_award_year', array('slug' => $current_year));
        }
    }
} 