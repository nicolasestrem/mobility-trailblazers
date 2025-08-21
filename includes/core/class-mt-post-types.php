<?php
/**
 * Post Types Registration
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Core;

use MobilityTrailblazers\Core\MT_Audit_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Post_Types
 *
 * Registers custom post types
 */
class MT_Post_Types {
    
    /**
     * Initialize post types
     *
     * @return void
     */
    public function init() {
        add_action('init', [$this, 'register_post_types']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box_data']);
    }
    
    /**
     * Register post types
     *
     * @return void
     */
    public function register_post_types() {
        // Register Candidate post type
        $this->register_candidate_post_type();
        
        // Register Jury Member post type
        $this->register_jury_member_post_type();
    }
    
    /**
     * Register Candidate post type
     *
     * @return void
     */
    private function register_candidate_post_type() {
        $labels = [
            'name'                  => _x('Candidates', 'Post type general name', 'mobility-trailblazers'),
            'singular_name'         => _x('Candidate', 'Post type singular name', 'mobility-trailblazers'),
            'menu_name'             => _x('Candidates', 'Admin Menu text', 'mobility-trailblazers'),
            'name_admin_bar'        => _x('Candidate', 'Add New on Toolbar', 'mobility-trailblazers'),
            'add_new'               => __('Add New', 'mobility-trailblazers'),
            'add_new_item'          => __('Add New Candidate', 'mobility-trailblazers'),
            'new_item'              => __('New Candidate', 'mobility-trailblazers'),
            'edit_item'             => __('Edit Candidate', 'mobility-trailblazers'),
            'view_item'             => __('View Candidate', 'mobility-trailblazers'),
            'all_items'             => __('All Candidates', 'mobility-trailblazers'),
            'search_items'          => __('Search Candidates', 'mobility-trailblazers'),
            'parent_item_colon'     => __('Parent Candidates:', 'mobility-trailblazers'),
            'not_found'             => __('No candidates found.', 'mobility-trailblazers'),
            'not_found_in_trash'    => __('No candidates found in Trash.', 'mobility-trailblazers'),
            'featured_image'        => _x('Candidate Photo', 'Overrides the "Featured Image" phrase', 'mobility-trailblazers'),
            'set_featured_image'    => _x('Set candidate photo', 'Overrides the "Set featured image" phrase', 'mobility-trailblazers'),
            'remove_featured_image' => _x('Remove candidate photo', 'Overrides the "Remove featured image" phrase', 'mobility-trailblazers'),
            'use_featured_image'    => _x('Use as candidate photo', 'Overrides the "Use as featured image" phrase', 'mobility-trailblazers'),
            'archives'              => _x('Candidate archives', 'The post type archive label', 'mobility-trailblazers'),
            'insert_into_item'      => _x('Insert into candidate', 'Overrides the "Insert into post" phrase', 'mobility-trailblazers'),
            'uploaded_to_this_item' => _x('Uploaded to this candidate', 'Overrides the "Uploaded to this post" phrase', 'mobility-trailblazers'),
            'filter_items_list'     => _x('Filter candidates list', 'Screen reader text', 'mobility-trailblazers'),
            'items_list_navigation' => _x('Candidates list navigation', 'Screen reader text', 'mobility-trailblazers'),
            'items_list'            => _x('Candidates list', 'Screen reader text', 'mobility-trailblazers'),
        ];
        
        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'mobility-trailblazers',
            'query_var'          => true,
            'rewrite'            => ['slug' => 'candidate'],
            'capability_type'    => ['mt_candidate', 'mt_candidates'],
            'map_meta_cap'       => true,
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
            'show_in_rest'       => true,
        ];
        
        register_post_type('mt_candidate', $args);
    }
    
    /**
     * Register Jury Member post type
     *
     * @return void
     */
    private function register_jury_member_post_type() {
        $labels = [
            'name'                  => _x('Jury Members', 'Post type general name', 'mobility-trailblazers'),
            'singular_name'         => _x('Jury Member', 'Post type singular name', 'mobility-trailblazers'),
            'menu_name'             => _x('Jury Members', 'Admin Menu text', 'mobility-trailblazers'),
            'name_admin_bar'        => _x('Jury Member', 'Add New on Toolbar', 'mobility-trailblazers'),
            'add_new'               => __('Add New', 'mobility-trailblazers'),
            'add_new_item'          => __('Add New Jury Member', 'mobility-trailblazers'),
            'new_item'              => __('New Jury Member', 'mobility-trailblazers'),
            'edit_item'             => __('Edit Jury Member', 'mobility-trailblazers'),
            'view_item'             => __('View Jury Member', 'mobility-trailblazers'),
            'all_items'             => __('All Jury Members', 'mobility-trailblazers'),
            'search_items'          => __('Search Jury Members', 'mobility-trailblazers'),
            'parent_item_colon'     => __('Parent Jury Members:', 'mobility-trailblazers'),
            'not_found'             => __('No jury members found.', 'mobility-trailblazers'),
            'not_found_in_trash'    => __('No jury members found in Trash.', 'mobility-trailblazers'),
            'featured_image'        => _x('Jury Member Photo', 'Overrides the "Featured Image" phrase', 'mobility-trailblazers'),
            'set_featured_image'    => _x('Set jury member photo', 'Overrides the "Set featured image" phrase', 'mobility-trailblazers'),
            'remove_featured_image' => _x('Remove jury member photo', 'Overrides the "Remove featured image" phrase', 'mobility-trailblazers'),
            'use_featured_image'    => _x('Use as jury member photo', 'Overrides the "Use as featured image" phrase', 'mobility-trailblazers'),
            'archives'              => _x('Jury Member archives', 'The post type archive label', 'mobility-trailblazers'),
            'insert_into_item'      => _x('Insert into jury member', 'Overrides the "Insert into post" phrase', 'mobility-trailblazers'),
            'uploaded_to_this_item' => _x('Uploaded to this jury member', 'Overrides the "Uploaded to this post" phrase', 'mobility-trailblazers'),
            'filter_items_list'     => _x('Filter jury members list', 'Screen reader text', 'mobility-trailblazers'),
            'items_list_navigation' => _x('Jury members list navigation', 'Screen reader text', 'mobility-trailblazers'),
            'items_list'            => _x('Jury members list', 'Screen reader text', 'mobility-trailblazers'),
        ];
        
        $args = [
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'mobility-trailblazers',
            'query_var'          => true,
            'rewrite'            => false,
            'capability_type'    => ['mt_jury_member', 'mt_jury_members'],
            'map_meta_cap'       => true,
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => ['title', 'editor', 'thumbnail'],
            'show_in_rest'       => true,
        ];
        
        register_post_type('mt_jury_member', $args);
    }
    
    /**
     * Add meta boxes
     *
     * @return void
     */
    public function add_meta_boxes() {
        // Candidate meta boxes
        add_meta_box(
            'mt_candidate_details',
            __('Candidate Details', 'mobility-trailblazers'),
            [$this, 'render_candidate_details_meta_box'],
            'mt_candidate',
            'normal',
            'high'
        );
        
        // Jury member meta boxes
        add_meta_box(
            'mt_jury_member_details',
            __('Jury Member Details', 'mobility-trailblazers'),
            [$this, 'render_jury_member_details_meta_box'],
            'mt_jury_member',
            'normal',
            'high'
        );
    }
    
    /**
     * Render candidate details meta box
     *
     * @param WP_Post $post Current post object
     * @return void
     */
    public function render_candidate_details_meta_box($post) {
        wp_nonce_field('mt_save_candidate_details', 'mt_candidate_details_nonce');
        
        $organization = get_post_meta($post->ID, '_mt_organization', true);
        $position = get_post_meta($post->ID, '_mt_position', true);
        $linkedin = get_post_meta($post->ID, '_mt_linkedin_url', true);
        $website = get_post_meta($post->ID, '_mt_website_url', true);
        ?>
        <div class="mt-meta-box">
            <p>
                <label for="mt_organization"><?php _e('Organization', 'mobility-trailblazers'); ?></label>
                <input type="text" id="mt_organization" name="mt_organization" value="<?php echo esc_attr($organization); ?>" class="widefat" />
            </p>
            <p>
                <label for="mt_position"><?php _e('Position', 'mobility-trailblazers'); ?></label>
                <input type="text" id="mt_position" name="mt_position" value="<?php echo esc_attr($position); ?>" class="widefat" />
            </p>
            <p>
                <label for="mt_linkedin"><?php _e('LinkedIn URL', 'mobility-trailblazers'); ?></label>
                <input type="url" id="mt_linkedin" name="mt_linkedin" value="<?php echo esc_url($linkedin); ?>" class="widefat" />
            </p>
            <p>
                <label for="mt_website"><?php _e('Website URL', 'mobility-trailblazers'); ?></label>
                <input type="url" id="mt_website" name="mt_website" value="<?php echo esc_url($website); ?>" class="widefat" />
            </p>
        </div>
        <?php
    }
    
    /**
     * Render jury member details meta box
     *
     * @param WP_Post $post Current post object
     * @return void
     */
    public function render_jury_member_details_meta_box($post) {
        wp_nonce_field('mt_save_jury_member_details', 'mt_jury_member_details_nonce');
        
        $user_id = get_post_meta($post->ID, '_mt_user_id', true);
        $expertise = get_post_meta($post->ID, '_mt_expertise', true);
        ?>
        <div class="mt-meta-box">
            <p>
                <label for="mt_user_id"><?php _e('WordPress User', 'mobility-trailblazers'); ?></label>
                <?php
                wp_dropdown_users([
                    'name' => 'mt_user_id',
                    'id' => 'mt_user_id',
                    'selected' => $user_id,
                    'show_option_none' => __('— Select User —', 'mobility-trailblazers'),
                    'option_none_value' => '',
                    'class' => 'widefat'
                ]);
                ?>
            </p>
            <p>
                <label for="mt_expertise"><?php _e('Area of Expertise', 'mobility-trailblazers'); ?></label>
                <textarea id="mt_expertise" name="mt_expertise" rows="3" class="widefat"><?php echo esc_textarea($expertise); ?></textarea>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save meta box data
     *
     * @param int $post_id Post ID
     * @return void
     */
    public function save_meta_box_data($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['mt_candidate_details_nonce']) && !isset($_POST['mt_jury_member_details_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (isset($_POST['mt_candidate_details_nonce']) && !wp_verify_nonce($_POST['mt_candidate_details_nonce'], 'mt_save_candidate_details')) {
            return;
        }
        
        if (isset($_POST['mt_jury_member_details_nonce']) && !wp_verify_nonce($_POST['mt_jury_member_details_nonce'], 'mt_save_jury_member_details')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (isset($_POST['post_type']) && 'mt_candidate' === $_POST['post_type']) {
            if (!current_user_can('edit_mt_candidate', $post_id)) {
                return;
            }
            
            // Save candidate data
            if (isset($_POST['mt_organization'])) {
                update_post_meta($post_id, '_mt_organization', sanitize_text_field($_POST['mt_organization']));
            }
            if (isset($_POST['mt_position'])) {
                update_post_meta($post_id, '_mt_position', sanitize_text_field($_POST['mt_position']));
            }
            if (isset($_POST['mt_linkedin'])) {
                update_post_meta($post_id, '_mt_linkedin_url', esc_url_raw($_POST['mt_linkedin']));
            }
            if (isset($_POST['mt_website'])) {
                update_post_meta($post_id, '_mt_website_url', esc_url_raw($_POST['mt_website']));
            }
            
            // Log candidate update
            MT_Audit_Logger::log('candidate_updated', 'candidate', $post_id, [
                'organization' => sanitize_text_field($_POST['mt_organization'] ?? ''),
                'position' => sanitize_text_field($_POST['mt_position'] ?? ''),
                'linkedin' => esc_url_raw($_POST['mt_linkedin'] ?? ''),
                'website' => esc_url_raw($_POST['mt_website'] ?? '')
            ]);
        }
        
        if (isset($_POST['post_type']) && 'mt_jury_member' === $_POST['post_type']) {
            if (!current_user_can('edit_mt_jury_member', $post_id)) {
                return;
            }
            
            // Save jury member data
            if (isset($_POST['mt_user_id'])) {
                update_post_meta($post_id, '_mt_user_id', intval($_POST['mt_user_id']));
            }
            if (isset($_POST['mt_expertise'])) {
                update_post_meta($post_id, '_mt_expertise', sanitize_textarea_field($_POST['mt_expertise']));
            }
            
            // Log jury member update
            MT_Audit_Logger::log('jury_member_updated', 'jury_member', $post_id, [
                'user_id' => intval($_POST['mt_user_id'] ?? 0),
                'expertise' => sanitize_textarea_field($_POST['mt_expertise'] ?? '')
            ]);
        }
    }
} 
