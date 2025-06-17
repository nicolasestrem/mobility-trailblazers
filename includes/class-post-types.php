<?php
/**
 * Post Types registration class
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Post_Types
 * Handles custom post type registration
 */
class MT_Post_Types {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register post types on init with priority 5
        add_action('init', array($this, 'register_post_types'), 5);
        
        // Add custom columns
        add_filter('manage_mt_candidate_posts_columns', array($this, 'add_candidate_columns'));
        add_action('manage_mt_candidate_posts_custom_column', array($this, 'render_candidate_columns'), 10, 2);
        
        add_filter('manage_mt_jury_posts_columns', array($this, 'add_jury_columns'));
        add_action('manage_mt_jury_posts_custom_column', array($this, 'render_jury_columns'), 10, 2);
        
        // Make columns sortable
        add_filter('manage_edit-mt_candidate_sortable_columns', array($this, 'make_candidate_columns_sortable'));
        add_filter('manage_edit-mt_jury_sortable_columns', array($this, 'make_jury_columns_sortable'));
        
        // Handle column sorting
        add_action('pre_get_posts', array($this, 'handle_column_sorting'));
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Register Candidate post type
        $this->register_candidate_post_type();
        
        // Register Jury Member post type
        $this->register_jury_post_type();
        
        // Register Backup post type
        $this->register_backup_post_type();
    }
    
    /**
     * Register Candidate post type
     */
    private function register_candidate_post_type() {
        $labels = array(
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
            'archives'              => _x('Candidate Archives', 'The post type archive label', 'mobility-trailblazers'),
            'insert_into_item'      => _x('Insert into candidate', 'Overrides the "Insert into post" phrase', 'mobility-trailblazers'),
            'uploaded_to_this_item' => _x('Uploaded to this candidate', 'Overrides the "Uploaded to this post" phrase', 'mobility-trailblazers'),
            'filter_items_list'     => _x('Filter candidates list', 'Screen reader text', 'mobility-trailblazers'),
            'items_list_navigation' => _x('Candidates list navigation', 'Screen reader text', 'mobility-trailblazers'),
            'items_list'            => _x('Candidates list', 'Screen reader text', 'mobility-trailblazers'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'candidate'),
            'capability_type'    => array('mt_candidate', 'mt_candidates'),
            'map_meta_cap'       => true,
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-businessperson',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest'       => true,
            'rest_base'          => 'mt-candidates',
        );
        
        register_post_type('mt_candidate', $args);
    }
    
    /**
     * Register Jury Member post type
     */
    private function register_jury_post_type() {
        $labels = array(
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
            'archives'              => _x('Jury Member Archives', 'The post type archive label', 'mobility-trailblazers'),
            'insert_into_item'      => _x('Insert into jury member', 'Overrides the "Insert into post" phrase', 'mobility-trailblazers'),
            'uploaded_to_this_item' => _x('Uploaded to this jury member', 'Overrides the "Uploaded to this post" phrase', 'mobility-trailblazers'),
            'filter_items_list'     => _x('Filter jury members list', 'Screen reader text', 'mobility-trailblazers'),
            'items_list_navigation' => _x('Jury members list navigation', 'Screen reader text', 'mobility-trailblazers'),
            'items_list'            => _x('Jury members list', 'Screen reader text', 'mobility-trailblazers'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'jury-member'),
            'capability_type'    => array('mt_jury_member', 'mt_jury_members'),
            'map_meta_cap'       => true,
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 26,
            'menu_icon'          => 'dashicons-groups',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest'       => true,
            'rest_base'          => 'mt-jury-members',
        );
        
        register_post_type('mt_jury_member', $args);
    }
    
    /**
     * Register Backup post type
     */
    private function register_backup_post_type() {
        $labels = array(
            'name'                  => _x('Vote Backups', 'Post type general name', 'mobility-trailblazers'),
            'singular_name'         => _x('Vote Backup', 'Post type singular name', 'mobility-trailblazers'),
            'menu_name'             => _x('Vote Backups', 'Admin Menu text', 'mobility-trailblazers'),
            'name_admin_bar'        => _x('Vote Backup', 'Add New on Toolbar', 'mobility-trailblazers'),
            'add_new'               => __('Create Backup', 'mobility-trailblazers'),
            'add_new_item'          => __('Create New Backup', 'mobility-trailblazers'),
            'new_item'              => __('New Backup', 'mobility-trailblazers'),
            'edit_item'             => __('View Backup', 'mobility-trailblazers'),
            'view_item'             => __('View Backup', 'mobility-trailblazers'),
            'all_items'             => __('All Backups', 'mobility-trailblazers'),
            'search_items'          => __('Search Backups', 'mobility-trailblazers'),
            'not_found'             => __('No backups found.', 'mobility-trailblazers'),
            'not_found_in_trash'    => __('No backups found in Trash.', 'mobility-trailblazers'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false, // Will be added as submenu
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => array('mt_backup', 'mt_backups'),
            'map_meta_cap'       => true,
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => array('title'),
            'show_in_rest'       => false,
        );
        
        register_post_type('mt_backup', $args);
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
                $new_columns['status'] = __('Status', 'mobility-trailblazers');
                $new_columns['assigned_jury'] = __('Assigned Jury', 'mobility-trailblazers');
                $new_columns['total_score'] = __('Total Score', 'mobility-trailblazers');
            }
        }
        
        // Remove date column and add it at the end
        unset($new_columns['date']);
        $new_columns['date'] = __('Date', 'mobility-trailblazers');
        
        return $new_columns;
    }
    
    /**
     * Render custom columns for candidates
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function render_candidate_columns($column, $post_id) {
        switch ($column) {
            case 'company':
                echo esc_html(get_post_meta($post_id, '_mt_company', true));
                break;
                
            case 'category':
                $terms = get_the_terms($post_id, 'mt_category');
                if ($terms && !is_wp_error($terms)) {
                    $categories = array();
                    foreach ($terms as $term) {
                        $categories[] = esc_html($term->name);
                    }
                    echo implode(', ', $categories);
                }
                break;
                
            case 'status':
                $status = get_post_meta($post_id, '_mt_status', true);
                $status_class = 'status-' . sanitize_html_class($status);
                echo '<span class="mt-status ' . $status_class . '">' . esc_html(ucfirst($status)) . '</span>';
                break;
                
            case 'assigned_jury':
                $jury_members = get_post_meta($post_id, '_mt_assigned_jury_members', true);
                if (is_array($jury_members) && !empty($jury_members)) {
                    echo count($jury_members) . ' ' . __('jury members', 'mobility-trailblazers');
                } else {
                    echo '—';
                }
                break;
                
            case 'total_score':
                global $wpdb;
                $table_name = $wpdb->prefix . 'mt_candidate_scores';
                $avg_score = $wpdb->get_var($wpdb->prepare(
                    "SELECT AVG(total_score) FROM $table_name WHERE candidate_id = %d AND is_active = 1",
                    $post_id
                ));
                
                if ($avg_score) {
                    echo '<strong>' . number_format($avg_score, 1) . '</strong> / 50';
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Add custom columns to jury members list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_jury_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['organization'] = __('Organization', 'mobility-trailblazers');
                $new_columns['role'] = __('Role', 'mobility-trailblazers');
                $new_columns['expertise'] = __('Expertise', 'mobility-trailblazers');
                $new_columns['assigned_candidates'] = __('Assigned Candidates', 'mobility-trailblazers');
                $new_columns['evaluations'] = __('Evaluations', 'mobility-trailblazers');
            }
        }
        
        // Remove date column and add it at the end
        unset($new_columns['date']);
        $new_columns['date'] = __('Date', 'mobility-trailblazers');
        
        return $new_columns;
    }
    
    /**
     * Render custom columns for jury members
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function render_jury_columns($column, $post_id) {
        switch ($column) {
            case 'organization':
                echo esc_html(get_post_meta($post_id, '_mt_organization', true));
                break;
                
            case 'role':
                $role = get_post_meta($post_id, '_mt_jury_role', true);
                if ($role) {
                    $role_labels = array(
                        'president' => __('President', 'mobility-trailblazers'),
                        'vice_president' => __('Vice President', 'mobility-trailblazers'),
                        'member' => __('Member', 'mobility-trailblazers'),
                    );
                    echo isset($role_labels[$role]) ? $role_labels[$role] : ucfirst($role);
                }
                break;
                
            case 'expertise':
                $expertise = get_post_meta($post_id, '_mt_expertise_areas', true);
                if (is_array($expertise)) {
                    echo esc_html(implode(', ', $expertise));
                }
                break;
                
            case 'assigned_candidates':
                global $wpdb;
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
                     WHERE meta_key = '_mt_assigned_jury_members' 
                     AND meta_value LIKE %s",
                    '%"' . $post_id . '"%'
                ));
                echo $count ? $count : '0';
                break;
                
            case 'evaluations':
                global $wpdb;
                $table_name = $wpdb->prefix . 'mt_candidate_scores';
                $completed = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE jury_member_id = %d AND is_active = 1",
                    $post_id
                ));
                
                $assigned = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
                     WHERE meta_key = '_mt_assigned_jury_members' 
                     AND meta_value LIKE %s",
                    '%"' . $post_id . '"%'
                ));
                
                if ($assigned > 0) {
                    $percentage = round(($completed / $assigned) * 100);
                    echo "<strong>$completed / $assigned</strong> ($percentage%)";
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Make candidate columns sortable
     *
     * @param array $columns Sortable columns
     * @return array Modified sortable columns
     */
    public function make_candidate_columns_sortable($columns) {
        $columns['company'] = 'company';
        $columns['status'] = 'status';
        $columns['total_score'] = 'total_score';
        
        return $columns;
    }
    
    /**
     * Make jury columns sortable
     *
     * @param array $columns Sortable columns
     * @return array Modified sortable columns
     */
    public function make_jury_columns_sortable($columns) {
        $columns['organization'] = 'organization';
        $columns['role'] = 'role';
        
        return $columns;
    }
    
    /**
     * Handle column sorting
     *
     * @param WP_Query $query The query object
     */
    public function handle_column_sorting($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        // Handle candidate sorting
        if ($query->get('post_type') === 'mt_candidate') {
            switch ($orderby) {
                case 'company':
                    $query->set('meta_key', '_mt_company');
                    $query->set('orderby', 'meta_value');
                    break;
                    
                case 'status':
                    $query->set('meta_key', '_mt_status');
                    $query->set('orderby', 'meta_value');
                    break;
                    
                case 'total_score':
                    // This requires a custom query - handled via filter
                    add_filter('posts_clauses', array($this, 'sort_by_total_score'), 10, 2);
                    break;
            }
        }
        
        // Handle jury member sorting
        if ($query->get('post_type') === 'mt_jury_member') {
            switch ($orderby) {
                case 'organization':
                    $query->set('meta_key', '_mt_organization');
                    $query->set('orderby', 'meta_value');
                    break;
                    
                case 'role':
                    $query->set('meta_key', '_mt_jury_role');
                    $query->set('orderby', 'meta_value');
                    break;
            }
        }
    }
    
    /**
     * Sort candidates by total score
     *
     * @param array $clauses SQL clauses
     * @param WP_Query $query The query object
     * @return array Modified clauses
     */
    public function sort_by_total_score($clauses, $query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('orderby') !== 'total_score') {
            return $clauses;
        }
        
        global $wpdb;
        
        $order = $query->get('order') === 'ASC' ? 'ASC' : 'DESC';
        
        $clauses['join'] .= " LEFT JOIN (
            SELECT candidate_id, AVG(total_score) as avg_score 
            FROM {$wpdb->prefix}mt_candidate_scores 
            WHERE is_active = 1 
            GROUP BY candidate_id
        ) AS scores ON {$wpdb->posts}.ID = scores.candidate_id";
        
        $clauses['orderby'] = "scores.avg_score $order, {$wpdb->posts}.post_date DESC";
        
        return $clauses;
    }
} 