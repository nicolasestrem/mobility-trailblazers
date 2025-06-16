<?php
/**
 * Taxonomies registration class
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Taxonomies
 * Handles custom taxonomy registration
 */
class MT_Taxonomies {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register taxonomies on init with priority 5
        add_action('init', array($this, 'register_taxonomies'), 5);
        
        // Add custom fields to category taxonomy
        add_action('mt_category_add_form_fields', array($this, 'add_category_fields'));
        add_action('mt_category_edit_form_fields', array($this, 'edit_category_fields'));
        
        // Save custom fields
        add_action('created_mt_category', array($this, 'save_category_fields'));
        add_action('edited_mt_category', array($this, 'save_category_fields'));
        
        // Add custom columns
        add_filter('manage_edit-mt_category_columns', array($this, 'add_category_columns'));
        add_filter('manage_mt_category_custom_column', array($this, 'render_category_columns'), 10, 3);
    }
    
    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        // Register Category taxonomy
        $this->register_category_taxonomy();
        
        // Register Phase taxonomy
        $this->register_phase_taxonomy();
        
        // Register Status taxonomy
        $this->register_status_taxonomy();
        
        // Register Award Year taxonomy
        $this->register_award_year_taxonomy();
    }
    
    /**
     * Register Category taxonomy
     */
    private function register_category_taxonomy() {
        $labels = array(
            'name'                       => _x('Categories', 'taxonomy general name', 'mobility-trailblazers'),
            'singular_name'              => _x('Category', 'taxonomy singular name', 'mobility-trailblazers'),
            'search_items'               => __('Search Categories', 'mobility-trailblazers'),
            'popular_items'              => __('Popular Categories', 'mobility-trailblazers'),
            'all_items'                  => __('All Categories', 'mobility-trailblazers'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit Category', 'mobility-trailblazers'),
            'update_item'                => __('Update Category', 'mobility-trailblazers'),
            'add_new_item'               => __('Add New Category', 'mobility-trailblazers'),
            'new_item_name'              => __('New Category Name', 'mobility-trailblazers'),
            'separate_items_with_commas' => __('Separate categories with commas', 'mobility-trailblazers'),
            'add_or_remove_items'        => __('Add or remove categories', 'mobility-trailblazers'),
            'choose_from_most_used'      => __('Choose from the most used categories', 'mobility-trailblazers'),
            'not_found'                  => __('No categories found.', 'mobility-trailblazers'),
            'menu_name'                  => __('Categories', 'mobility-trailblazers'),
        );
        
        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => array('slug' => 'mt-category'),
            'show_in_rest'          => true,
            'rest_base'             => 'mt-categories',
        );
        
        register_taxonomy('mt_category', array('mt_candidate'), $args);
        
        // Insert default categories if they don't exist
        $this->insert_default_categories();
    }
    
    /**
     * Register Phase taxonomy
     */
    private function register_phase_taxonomy() {
        $labels = array(
            'name'                       => _x('Phases', 'taxonomy general name', 'mobility-trailblazers'),
            'singular_name'              => _x('Phase', 'taxonomy singular name', 'mobility-trailblazers'),
            'search_items'               => __('Search Phases', 'mobility-trailblazers'),
            'all_items'                  => __('All Phases', 'mobility-trailblazers'),
            'edit_item'                  => __('Edit Phase', 'mobility-trailblazers'),
            'update_item'                => __('Update Phase', 'mobility-trailblazers'),
            'add_new_item'               => __('Add New Phase', 'mobility-trailblazers'),
            'new_item_name'              => __('New Phase Name', 'mobility-trailblazers'),
            'menu_name'                  => __('Phases', 'mobility-trailblazers'),
        );
        
        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'mt-phase'),
            'show_in_rest'          => true,
            'rest_base'             => 'mt-phases',
        );
        
        register_taxonomy('mt_phase', array('mt_candidate'), $args);
        
        // Insert default phases
        $this->insert_default_phases();
    }
    
    /**
     * Register Status taxonomy
     */
    private function register_status_taxonomy() {
        $labels = array(
            'name'                       => _x('Statuses', 'taxonomy general name', 'mobility-trailblazers'),
            'singular_name'              => _x('Status', 'taxonomy singular name', 'mobility-trailblazers'),
            'search_items'               => __('Search Statuses', 'mobility-trailblazers'),
            'all_items'                  => __('All Statuses', 'mobility-trailblazers'),
            'edit_item'                  => __('Edit Status', 'mobility-trailblazers'),
            'update_item'                => __('Update Status', 'mobility-trailblazers'),
            'add_new_item'               => __('Add New Status', 'mobility-trailblazers'),
            'new_item_name'              => __('New Status Name', 'mobility-trailblazers'),
            'menu_name'                  => __('Statuses', 'mobility-trailblazers'),
        );
        
        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'mt-status'),
            'show_in_rest'          => true,
            'rest_base'             => 'mt-statuses',
        );
        
        register_taxonomy('mt_status', array('mt_candidate', 'mt_jury'), $args);
        
        // Insert default statuses
        $this->insert_default_statuses();
    }
    
    /**
     * Register Award Year taxonomy
     */
    private function register_award_year_taxonomy() {
        $labels = array(
            'name'                       => _x('Award Years', 'taxonomy general name', 'mobility-trailblazers'),
            'singular_name'              => _x('Award Year', 'taxonomy singular name', 'mobility-trailblazers'),
            'search_items'               => __('Search Award Years', 'mobility-trailblazers'),
            'all_items'                  => __('All Award Years', 'mobility-trailblazers'),
            'edit_item'                  => __('Edit Award Year', 'mobility-trailblazers'),
            'update_item'                => __('Update Award Year', 'mobility-trailblazers'),
            'add_new_item'               => __('Add New Award Year', 'mobility-trailblazers'),
            'new_item_name'              => __('New Award Year', 'mobility-trailblazers'),
            'menu_name'                  => __('Award Years', 'mobility-trailblazers'),
        );
        
        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'award-year'),
            'show_in_rest'          => true,
            'rest_base'             => 'mt-award-years',
        );
        
        register_taxonomy('mt_award_year', array('mt_candidate', 'mt_jury'), $args);
        
        // Insert current year if it doesn't exist
        $this->insert_current_award_year();
    }
    
    /**
     * Insert default categories
     */
    private function insert_default_categories() {
        $categories = array(
            'new-mobility-services' => array(
                'name' => __('New Mobility Services', 'mobility-trailblazers'),
                'description' => __('Innovative mobility services and platforms', 'mobility-trailblazers'),
                'color' => '#3498db',
                'icon' => 'dashicons-car'
            ),
            'sustainable-mobility' => array(
                'name' => __('Sustainable Mobility', 'mobility-trailblazers'),
                'description' => __('Eco-friendly and sustainable transportation solutions', 'mobility-trailblazers'),
                'color' => '#27ae60',
                'icon' => 'dashicons-admin-site-alt3'
            ),
            'urban-mobility' => array(
                'name' => __('Urban Mobility', 'mobility-trailblazers'),
                'description' => __('Solutions for city transportation challenges', 'mobility-trailblazers'),
                'color' => '#e74c3c',
                'icon' => 'dashicons-building'
            ),
            'mobility-technology' => array(
                'name' => __('Mobility Technology', 'mobility-trailblazers'),
                'description' => __('Technological innovations in transportation', 'mobility-trailblazers'),
                'color' => '#9b59b6',
                'icon' => 'dashicons-admin-generic'
            ),
            'mobility-infrastructure' => array(
                'name' => __('Mobility Infrastructure', 'mobility-trailblazers'),
                'description' => __('Infrastructure solutions for modern mobility', 'mobility-trailblazers'),
                'color' => '#f39c12',
                'icon' => 'dashicons-admin-network'
            )
        );
        
        foreach ($categories as $slug => $category) {
            if (!term_exists($slug, 'mt_category')) {
                $term = wp_insert_term(
                    $category['name'],
                    'mt_category',
                    array(
                        'slug' => $slug,
                        'description' => $category['description']
                    )
                );
                
                if (!is_wp_error($term)) {
                    // Save custom fields
                    update_term_meta($term['term_id'], '_mt_category_color', $category['color']);
                    update_term_meta($term['term_id'], '_mt_category_icon', $category['icon']);
                }
            }
        }
    }
    
    /**
     * Insert default phases
     */
    private function insert_default_phases() {
        $phases = array(
            'nomination' => __('Nomination', 'mobility-trailblazers'),
            'screening' => __('Screening', 'mobility-trailblazers'),
            'evaluation' => __('Evaluation', 'mobility-trailblazers'),
            'selection' => __('Selection', 'mobility-trailblazers'),
            'announcement' => __('Announcement', 'mobility-trailblazers')
        );
        
        foreach ($phases as $slug => $name) {
            if (!term_exists($slug, 'mt_phase')) {
                wp_insert_term($name, 'mt_phase', array('slug' => $slug));
            }
        }
    }
    
    /**
     * Insert default statuses
     */
    private function insert_default_statuses() {
        $statuses = array(
            'active' => __('Active', 'mobility-trailblazers'),
            'inactive' => __('Inactive', 'mobility-trailblazers'),
            'pending' => __('Pending', 'mobility-trailblazers'),
            'approved' => __('Approved', 'mobility-trailblazers'),
            'rejected' => __('Rejected', 'mobility-trailblazers'),
            'shortlisted' => __('Shortlisted', 'mobility-trailblazers'),
            'winner' => __('Winner', 'mobility-trailblazers')
        );
        
        foreach ($statuses as $slug => $name) {
            if (!term_exists($slug, 'mt_status')) {
                wp_insert_term($name, 'mt_status', array('slug' => $slug));
            }
        }
    }
    
    /**
     * Insert current award year
     */
    private function insert_current_award_year() {
        $current_year = date('Y');
        
        if (!term_exists($current_year, 'mt_award_year')) {
            wp_insert_term($current_year, 'mt_award_year', array('slug' => $current_year));
        }
    }
    
    /**
     * Add custom fields to category add form
     */
    public function add_category_fields() {
        ?>
        <div class="form-field">
            <label for="mt_category_color"><?php _e('Category Color', 'mobility-trailblazers'); ?></label>
            <input type="color" name="mt_category_color" id="mt_category_color" value="#3498db">
            <p class="description"><?php _e('Choose a color to represent this category.', 'mobility-trailblazers'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="mt_category_icon"><?php _e('Category Icon', 'mobility-trailblazers'); ?></label>
            <input type="text" name="mt_category_icon" id="mt_category_icon" value="">
            <p class="description"><?php _e('Enter a dashicon class (e.g., dashicons-car).', 'mobility-trailblazers'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Add custom fields to category edit form
     *
     * @param WP_Term $term Current taxonomy term object
     */
    public function edit_category_fields($term) {
        $color = get_term_meta($term->term_id, '_mt_category_color', true);
        $icon = get_term_meta($term->term_id, '_mt_category_icon', true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="mt_category_color"><?php _e('Category Color', 'mobility-trailblazers'); ?></label>
            </th>
            <td>
                <input type="color" name="mt_category_color" id="mt_category_color" value="<?php echo esc_attr($color); ?>">
                <p class="description"><?php _e('Choose a color to represent this category.', 'mobility-trailblazers'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="mt_category_icon"><?php _e('Category Icon', 'mobility-trailblazers'); ?></label>
            </th>
            <td>
                <input type="text" name="mt_category_icon" id="mt_category_icon" value="<?php echo esc_attr($icon); ?>">
                <p class="description"><?php _e('Enter a dashicon class (e.g., dashicons-car).', 'mobility-trailblazers'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save category custom fields
     *
     * @param int $term_id Term ID
     */
    public function save_category_fields($term_id) {
        if (isset($_POST['mt_category_color'])) {
            update_term_meta($term_id, '_mt_category_color', sanitize_hex_color($_POST['mt_category_color']));
        }
        
        if (isset($_POST['mt_category_icon'])) {
            update_term_meta($term_id, '_mt_category_icon', sanitize_text_field($_POST['mt_category_icon']));
        }
    }
    
    /**
     * Add custom columns to category list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_category_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            if ($key === 'name') {
                $new_columns[$key] = $value;
                $new_columns['color'] = __('Color', 'mobility-trailblazers');
                $new_columns['icon'] = __('Icon', 'mobility-trailblazers');
            } else {
                $new_columns[$key] = $value;
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render custom columns for categories
     *
     * @param string $content Column content
     * @param string $column_name Column name
     * @param int $term_id Term ID
     * @return string Column content
     */
    public function render_category_columns($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'color':
                $color = get_term_meta($term_id, '_mt_category_color', true);
                if ($color) {
                    $content = '<span style="display: inline-block; width: 30px; height: 30px; background-color: ' . esc_attr($color) . '; border: 1px solid #ccc; border-radius: 3px;"></span>';
                }
                break;
                
            case 'icon':
                $icon = get_term_meta($term_id, '_mt_category_icon', true);
                if ($icon) {
                    $content = '<span class="dashicons ' . esc_attr($icon) . '" style="font-size: 24px;"></span>';
                }
                break;
        }
        
        return $content;
    }
} 