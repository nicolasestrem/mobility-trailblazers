<?php
/**
 * Taxonomies Registration
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
 * Class MT_Taxonomies
 *
 * Registers custom taxonomies
 */
class MT_Taxonomies {
    
    /**
     * Initialize taxonomies
     *
     * @return void
     */
    public function init() {
        add_action('init', [$this, 'register_taxonomies']);
    }
    
    /**
     * Register taxonomies
     *
     * @return void
     */
    public function register_taxonomies() {
        $this->register_award_category_taxonomy();
    }
    
    /**
     * Register Award Category taxonomy
     *
     * @return void
     */
    private function register_award_category_taxonomy() {
        $labels = [
            'name'                       => _x('Award Categories', 'taxonomy general name', 'mobility-trailblazers'),
            'singular_name'              => _x('Award Category', 'taxonomy singular name', 'mobility-trailblazers'),
            'search_items'               => __('Search Award Categories', 'mobility-trailblazers'),
            'popular_items'              => __('Popular Award Categories', 'mobility-trailblazers'),
            'all_items'                  => __('All Award Categories', 'mobility-trailblazers'),
            'parent_item'                => __('Parent Award Category', 'mobility-trailblazers'),
            'parent_item_colon'          => __('Parent Award Category:', 'mobility-trailblazers'),
            'edit_item'                  => __('Edit Award Category', 'mobility-trailblazers'),
            'update_item'                => __('Update Award Category', 'mobility-trailblazers'),
            'add_new_item'               => __('Add New Award Category', 'mobility-trailblazers'),
            'new_item_name'              => __('New Award Category Name', 'mobility-trailblazers'),
            'separate_items_with_commas' => __('Separate award categories with commas', 'mobility-trailblazers'),
            'add_or_remove_items'        => __('Add or remove award categories', 'mobility-trailblazers'),
            'choose_from_most_used'      => __('Choose from the most used award categories', 'mobility-trailblazers'),
            'not_found'                  => __('No award categories found.', 'mobility-trailblazers'),
            'menu_name'                  => __('Award Categories', 'mobility-trailblazers'),
            'back_to_items'              => __('← Back to Award Categories', 'mobility-trailblazers'),
        ];
        
        $args = [
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_nav_menus'     => true,
            'show_tagcloud'         => false,
            'rewrite'               => ['slug' => 'award-category'],
            'show_in_rest'          => true,
            'meta_box_cb'           => 'post_categories_meta_box',
            'capabilities'          => [
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_mt_candidates'
            ],
        ];
        
        register_taxonomy('mt_award_category', ['mt_candidate'], $args);
        
        // Add default categories on activation
        $this->maybe_create_default_categories();
    }
    
    /**
     * Create default award categories if they don't exist
     *
     * @return void
     */
    private function maybe_create_default_categories() {
        // Only create on activation
        if (!get_transient('mt_activation_redirect')) {
            return;
        }
        
        $default_categories = [
            __('Innovation Leader', 'mobility-trailblazers') => __('Recognizing breakthrough innovations in mobility', 'mobility-trailblazers'),
            __('Sustainability Champion', 'mobility-trailblazers') => __('Honoring environmental leadership in transportation', 'mobility-trailblazers'),
            __('Digital Pioneer', 'mobility-trailblazers') => __('Celebrating digital transformation in mobility', 'mobility-trailblazers'),
            __('Community Impact', 'mobility-trailblazers') => __('Acknowledging positive community contributions', 'mobility-trailblazers'),
            __('Future Visionary', 'mobility-trailblazers') => __('Recognizing forward-thinking mobility concepts', 'mobility-trailblazers')
        ];
        
        foreach ($default_categories as $name => $description) {
            if (!term_exists($name, 'mt_award_category')) {
                wp_insert_term($name, 'mt_award_category', [
                    'description' => $description
                ]);
            }
        }
    }
} 
