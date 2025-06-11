<?php
/**
 * Plugin Name: Mobility Trailblazers
 * Description: Award system for managing mobility trailblazers, candidates, jury members, and voting process
 * Version: 2.0.1
 * Author: Nicolas Estrem
 * Text Domain: mobility-trailblazers
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MT_PLUGIN_VERSION', '1.0.0');
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Mobility Trailblazers Class
 */
class MobilityTrailblazers {
    
    public function __construct() {
        add_action('init', array($this, 'create_post_types'));
        add_action('init', array($this, 'create_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts')); // Add this for Chart.js
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    /**
     * Create Custom Post Types
     */
    public function create_post_types() {
        
        // Candidates Post Type
        register_post_type('candidate', array(
            'labels' => array(
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
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-awards',
            'show_in_rest' => true,
        ));
        
        // Jury Members Post Type
        register_post_type('jury_member', array(
            'labels' => array(
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
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-groups',
            'show_in_rest' => true,
        ));
    }
    
    /**
     * Create Taxonomies
     */
    public function create_taxonomies() {
        
        // Candidate Categories
        register_taxonomy('candidate_category', 'candidate', array(
            'labels' => array(
                'name' => __('Categories', 'mobility-trailblazers'),
                'singular_name' => __('Category', 'mobility-trailblazers'),
                'add_new_item' => __('Add New Category', 'mobility-trailblazers'),
                'edit_item' => __('Edit Category', 'mobility-trailblazers'),
                'update_item' => __('Update Category', 'mobility-trailblazers'),
                'view_item' => __('View Category', 'mobility-trailblazers'),
                'search_items' => __('Search Categories', 'mobility-trailblazers'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
        ));
        
        // Candidate Stage
        register_taxonomy('candidate_stage', 'candidate', array(
            'labels' => array(
                'name' => __('Stages', 'mobility-trailblazers'),
                'singular_name' => __('Stage', 'mobility-trailblazers'),
            ),
            'hierarchical' => false,
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
        ));
        
        // Insert default terms
        if (!term_exists('Established Companies', 'candidate_category')) {
            wp_insert_term('Established Companies', 'candidate_category');
            wp_insert_term('Start-ups & Scale-ups', 'candidate_category');
            wp_insert_term('Politics & Public Companies', 'candidate_category');
        }
        
        if (!term_exists('Database', 'candidate_stage')) {
            wp_insert_term('Database', 'candidate_stage');
            wp_insert_term('Top 200', 'candidate_stage');
            wp_insert_term('Top 50', 'candidate_stage');
            wp_insert_term('Top 25', 'candidate_stage');
        }
    }
    
    /**
     * Register Shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('mt_voting_interface', array($this, 'voting_interface_shortcode'));
        add_shortcode('mt_jury_dashboard', array($this, 'jury_dashboard_shortcode'));
        add_shortcode('mt_candidate_grid', array($this, 'candidate_grid_shortcode'));
        add_shortcode('mt_voting_progress', array($this, 'voting_progress_shortcode'));
    }
    
    /**
     * Voting Interface Shortcode
     */
    public function voting_interface_shortcode($atts) {
        $atts = shortcode_atts(array(
            'layout' => 'grid',
            'show_progress' => 'true',
            'show_deadline' => 'true'
        ), $atts);
        
        if (!current_user_can('vote_on_candidates')) {
            return '<div class="mt-access-denied">You do not have permission to access the voting interface.</div>';
        }
        
        wp_enqueue_script('mt-voting-interface');
        wp_enqueue_style('mt-voting-styles');
        
        ob_start();
        ?>
        <div class="mt-voting-interface" data-layout="<?php echo esc_attr($atts['layout']); ?>">
            <?php if ($atts['show_progress'] === 'true'): ?>
                <div class="mt-voting-progress-header" id="mtVotingProgress">
                    <div class="mt-loading">
                        <div class="mt-spinner"></div>
                        <p><?php echo esc_html__('Loading voting progress...', 'mobility-trailblazers'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_deadline'] === 'true'): ?>
                <div class="mt-deadline-warning" id="mtDeadlineWarning" style="display: none;">
                    <!-- Deadline warning will be shown if needed -->
                </div>
            <?php endif; ?>
            
            <div class="mt-candidates-container" id="mtCandidatesContainer">
                <div class="mt-loading">
                    <div class="mt-spinner"></div>
                    <p><?php echo esc_html__('Loading your assigned candidates...', 'mobility-trailblazers'); ?></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Jury Dashboard Shortcode
     */
    public function jury_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_stats' => 'true',
            'show_recent_activity' => 'true'
        ), $atts);
        
        if (!current_user_can('vote_on_candidates')) {
            return '<div class="mt-access-denied">Access denied.</div>';
        }
        
        wp_enqueue_script('mt-voting-interface');
        wp_enqueue_style('mt-voting-styles');
        
        ob_start();
        ?>
        <div class="mt-jury-dashboard">
            <div class="mt-dashboard-header">
                <h2><?php echo esc_html__('Welcome back,', 'mobility-trailblazers'); ?> <?php echo wp_get_current_user()->display_name; ?></h2>
                <p><?php echo esc_html__('Your contribution to selecting the next generation of mobility trailblazers', 'mobility-trailblazers'); ?></p>
            </div>
            
            <?php if ($atts['show_stats'] === 'true'): ?>
                <div class="mt-dashboard-stats" id="mtDashboardStats">
                    <div class="mt-loading">
                        <div class="mt-spinner"></div>
                        <p><?php echo esc_html__('Loading statistics...', 'mobility-trailblazers'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="mt-dashboard-actions">
                <a href="#voting-interface" class="mt-btn mt-btn-primary"><?php echo esc_html__('Continue Voting', 'mobility-trailblazers'); ?></a>
                <a href="<?php echo admin_url('edit.php?post_type=candidate'); ?>" class="mt-btn mt-btn-secondary"><?php echo esc_html__('View All Candidates', 'mobility-trailblazers'); ?></a>
            </div>
            
            <?php if ($atts['show_recent_activity'] === 'true'): ?>
                <div class="mt-recent-activity" id="mtRecentActivity">
                    <div class="mt-loading">
                        <div class="mt-spinner"></div>
                        <p><?php echo esc_html__('Loading recent activity...', 'mobility-trailblazers'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Candidate Grid Shortcode
     */
    public function candidate_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'stage' => '',
            'limit' => 12,
            'show_voting' => 'false',
            'layout' => 'grid',
            'show_excerpt' => 'true',
            'show_company' => 'true',
            'show_categories' => 'true'
        ), $atts);
        
        wp_enqueue_style('mt-voting-styles');
        
        $query_args = array(
            'post_type' => 'candidate',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish'
        );
        
        // Add taxonomy filters
        $tax_query = array();
        
        if (!empty($atts['category'])) {
            $tax_query[] = array(
                'taxonomy' => 'candidate_category',
                'field' => 'slug',
                'terms' => $atts['category']
            );
        }
        
        if (!empty($atts['stage'])) {
            $tax_query[] = array(
                'taxonomy' => 'candidate_stage',
                'field' => 'slug',
                'terms' => $atts['stage']
            );
        }
        
        if (!empty($tax_query)) {
            $query_args['tax_query'] = $tax_query;
        }
        
        $candidates = new WP_Query($query_args);
        
        ob_start();
        ?>
        <div class="mt-candidate-grid mt-layout-<?php echo esc_attr($atts['layout']); ?>">
            <?php if ($candidates->have_posts()): ?>
                <div class="mt-candidates-wrapper">
                    <?php while ($candidates->have_posts()): $candidates->the_post(); ?>
                        <div class="mt-candidate-card" data-candidate-id="<?php echo get_the_ID(); ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <div class="mt-candidate-image">
                                    <?php the_post_thumbnail('medium'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-candidate-content">
                                <h3 class="mt-candidate-name"><?php the_title(); ?></h3>
                                
                                <?php if ($atts['show_company'] === 'true'): ?>
                                    <div class="mt-candidate-meta">
                                        <?php 
                                        $company = get_post_meta(get_the_ID(), '_candidate_company', true);
                                        $position = get_post_meta(get_the_ID(), '_candidate_position', true);
                                        ?>
                                        <?php if ($company): ?>
                                            <span class="mt-company"><?php echo esc_html($company); ?></span>
                                        <?php endif; ?>
                                        <?php if ($position): ?>
                                            <span class="mt-position"><?php echo esc_html($position); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($atts['show_categories'] === 'true'): ?>
                                    <div class="mt-candidate-categories">
                                        <?php 
                                        $categories = wp_get_post_terms(get_the_ID(), 'candidate_category');
                                        foreach ($categories as $category):
                                        ?>
                                            <span class="mt-category-tag"><?php echo esc_html($category->name); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($atts['show_excerpt'] === 'true'): ?>
                                    <div class="mt-candidate-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), 30); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($atts['show_voting'] === 'true' && current_user_can('vote_on_candidates')): ?>
                                    <div class="mt-voting-quick-access">
                                        <button class="mt-btn mt-btn-small mt-vote-btn" data-candidate-id="<?php echo get_the_ID(); ?>">
                                            <?php echo esc_html__('Vote on Candidate', 'mobility-trailblazers'); ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-candidate-actions">
                                    <a href="<?php the_permalink(); ?>" class="mt-btn mt-btn-outline"><?php echo esc_html__('View Details', 'mobility-trailblazers'); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <?php if ($candidates->max_num_pages > 1): ?>
                    <div class="mt-pagination">
                        <?php 
                        echo paginate_links(array(
                            'total' => $candidates->max_num_pages,
                            'current' => max(1, get_query_var('paged')),
                            'format' => '?paged=%#%',
                            'show_all' => false,
                            'end_size' => 1,
                            'mid_size' => 2,
                            'prev_next' => true,
                            'prev_text' => '← ' . esc_html__('Previous', 'mobility-trailblazers'),
                            'next_text' => esc_html__('Next', 'mobility-trailblazers') . ' →',
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="mt-no-candidates">
                    <h4><?php echo esc_html__('No candidates found', 'mobility-trailblazers'); ?></h4>
                    <p><?php echo esc_html__('There are no candidates matching your criteria at this time.', 'mobility-trailblazers'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Voting Progress Shortcode
     */
    public function voting_progress_shortcode($atts) {
        $atts = shortcode_atts(array(
            'widget_type' => 'dashboard',
            'show_phase_info' => 'true',
            'show_deadline' => 'true',
            'admin_only' => 'false'
        ), $atts);
        
        // Check permissions for admin-only view
        if ($atts['admin_only'] === 'true' && !current_user_can('manage_voting_phases')) {
            return '<div class="mt-access-denied">' . 
                   esc_html__('This widget is restricted to administrators only.', 'mobility-trailblazers') . 
                   '</div>';
        }
        
        wp_enqueue_script('mt-voting-interface');
        wp_enqueue_style('mt-voting-styles');
        
        ob_start();
        ?>
        <div class="mt-voting-progress-widget mt-widget-type-<?php echo esc_attr($atts['widget_type']); ?>" 
             data-settings="<?php echo esc_attr(json_encode($atts)); ?>">
            
            <?php if ($atts['widget_type'] === 'dashboard'): ?>
                <div class="mt-voting-progress-header">
                    <h2><?php echo esc_html__('Mobility Trailblazers 2025 - Voting Progress', 'mobility-trailblazers'); ?></h2>
                    <p><?php echo esc_html__('Real-time overview of the jury voting process', 'mobility-trailblazers'); ?></p>
                    
                    <?php if ($atts['show_phase_info'] === 'true'): ?>
                        <div class="mt-phase-info" id="mtPhaseInfo">
                            <div class="mt-loading">
                                <div class="mt-spinner"></div>
                                <p><?php echo esc_html__('Loading phase information...', 'mobility-trailblazers'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-progress-stats" id="mtProgressStats">
                        <div class="mt-loading">
                            <div class="mt-spinner"></div>
                            <p><?php echo esc_html__('Loading statistics...', 'mobility-trailblazers'); ?></p>
                        </div>
                    </div>
                </div>
                
                <?php if ($atts['show_deadline'] === 'true'): ?>
                    <div class="mt-deadline-info" id="mtDeadlineInfo">
                        <!-- Deadline countdown will be populated by JavaScript -->
                    </div>
                <?php endif; ?>
                
            <?php elseif ($atts['widget_type'] === 'mini_widget'): ?>
                <div class="mt-mini-progress-widget">
                    <div class="mt-mini-content">
                        <div class="mt-mini-icon">📊</div>
                        <div class="mt-mini-stats">
                            <div class="mt-mini-number" id="mtMiniNumber">
                                <?php echo esc_html__('Loading...', 'mobility-trailblazers'); ?>
                            </div>
                            <div class="mt-mini-label">
                                <?php echo esc_html__('Votes Completed', 'mobility-trailblazers'); ?>
                            </div>
                        </div>
                        <div class="mt-mini-percentage" id="mtMiniPercentage">0%</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add Meta Boxes
     */
    public function add_meta_boxes() {
        
        // Candidate Details
        add_meta_box(
            'candidate_details',
            __('Candidate Details', 'mobility-trailblazers'),
            array($this, 'candidate_details_callback'),
            'candidate',
            'normal',
            'high'
        );
        
        // Jury Member Details
        add_meta_box(
            'jury_details',
            __('Jury Member Details', 'mobility-trailblazers'),
            array($this, 'jury_details_callback'),
            'jury_member',
            'normal',
            'high'
        );
    }
    
    /**
     * Candidate Details Meta Box
     */
    public function candidate_details_callback($post) {
        wp_nonce_field('candidate_details_nonce', 'candidate_details_nonce');
        
        $company = get_post_meta($post->ID, '_candidate_company', true);
        $position = get_post_meta($post->ID, '_candidate_position', true);
        $achievements = get_post_meta($post->ID, '_candidate_achievements', true);
        $innovation_description = get_post_meta($post->ID, '_candidate_innovation', true);
        $website = get_post_meta($post->ID, '_candidate_website', true);
        $linkedin = get_post_meta($post->ID, '_candidate_linkedin', true);
        
        echo '<table class="form-table">';
        echo '<tr><th><label for="candidate_company">' . __('Company', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="text" id="candidate_company" name="candidate_company" value="' . esc_attr($company) . '" style="width: 100%;" /></td></tr>';
        
        echo '<tr><th><label for="candidate_position">' . __('Position', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="text" id="candidate_position" name="candidate_position" value="' . esc_attr($position) . '" style="width: 100%;" /></td></tr>';
        
        echo '<tr><th><label for="candidate_website">' . __('Website', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="url" id="candidate_website" name="candidate_website" value="' . esc_attr($website) . '" style="width: 100%;" /></td></tr>';
        
        echo '<tr><th><label for="candidate_linkedin">' . __('LinkedIn', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="url" id="candidate_linkedin" name="candidate_linkedin" value="' . esc_attr($linkedin) . '" style="width: 100%;" /></td></tr>';
        
        echo '<tr><th><label for="candidate_achievements">' . __('Key Achievements', 'mobility-trailblazers') . '</label></th>';
        echo '<td><textarea id="candidate_achievements" name="candidate_achievements" rows="4" style="width: 100%;">' . esc_textarea($achievements) . '</textarea></td></tr>';
        
        echo '<tr><th><label for="candidate_innovation">' . __('Innovation Description', 'mobility-trailblazers') . '</label></th>';
        echo '<td><textarea id="candidate_innovation" name="candidate_innovation" rows="6" style="width: 100%;">' . esc_textarea($innovation_description) . '</textarea></td></tr>';
        
        echo '</table>';
    }
    
    /**
     * Jury Member Details Meta Box
     */
    public function jury_details_callback($post) {
        wp_nonce_field('jury_details_nonce', 'jury_details_nonce');
        
        $company = get_post_meta($post->ID, '_jury_company', true);
        $position = get_post_meta($post->ID, '_jury_position', true);
        $expertise = get_post_meta($post->ID, '_jury_expertise', true);
        $bio = get_post_meta($post->ID, '_jury_bio', true);
        $linkedin = get_post_meta($post->ID, '_jury_linkedin', true);
        
        echo '<table class="form-table">';
        echo '<tr><th><label for="jury_company">' . __('Company', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="text" id="jury_company" name="jury_company" value="' . esc_attr($company) . '" style="width: 100%;" /></td></tr>';
        
        echo '<tr><th><label for="jury_position">' . __('Position', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="text" id="jury_position" name="jury_position" value="' . esc_attr($position) . '" style="width: 100%;" /></td></tr>';
        
        echo '<tr><th><label for="jury_expertise">' . __('Expertise Areas', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="text" id="jury_expertise" name="jury_expertise" value="' . esc_attr($expertise) . '" style="width: 100%;" placeholder="' . esc_attr__('e.g., Automotive, Innovation, Sustainability', 'mobility-trailblazers') . '" /></td></tr>';
        
        echo '<tr><th><label for="jury_linkedin">' . __('LinkedIn', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="url" id="jury_linkedin" name="jury_linkedin" value="' . esc_attr($linkedin) . '" style="width: 100%;" /></td></tr>';
        
        echo '<tr><th><label for="jury_bio">' . __('Biography', 'mobility-trailblazers') . '</label></th>';
        echo '<td><textarea id="jury_bio" name="jury_bio" rows="6" style="width: 100%;">' . esc_textarea($bio) . '</textarea></td></tr>';
        
        echo '</table>';
    }
    
    /**
     * Save Meta Box Data
     */
    public function save_meta_boxes($post_id) {
        // Candidate meta
        if (isset($_POST['candidate_details_nonce']) && wp_verify_nonce($_POST['candidate_details_nonce'], 'candidate_details_nonce')) {
            update_post_meta($post_id, '_candidate_company', sanitize_text_field($_POST['candidate_company']));
            update_post_meta($post_id, '_candidate_position', sanitize_text_field($_POST['candidate_position']));
            update_post_meta($post_id, '_candidate_website', esc_url_raw($_POST['candidate_website']));
            update_post_meta($post_id, '_candidate_linkedin', esc_url_raw($_POST['candidate_linkedin']));
            update_post_meta($post_id, '_candidate_achievements', sanitize_textarea_field($_POST['candidate_achievements']));
            update_post_meta($post_id, '_candidate_innovation', sanitize_textarea_field($_POST['candidate_innovation']));
        }
        
        // Jury meta
        if (isset($_POST['jury_details_nonce']) && wp_verify_nonce($_POST['jury_details_nonce'], 'jury_details_nonce')) {
            update_post_meta($post_id, '_jury_company', sanitize_text_field($_POST['jury_company']));
            update_post_meta($post_id, '_jury_position', sanitize_text_field($_POST['jury_position']));
            update_post_meta($post_id, '_jury_expertise', sanitize_text_field($_POST['jury_expertise']));
            update_post_meta($post_id, '_jury_linkedin', esc_url_raw($_POST['jury_linkedin']));
            update_post_meta($post_id, '_jury_bio', sanitize_textarea_field($_POST['jury_bio']));
        }
    }
    
    /**
     * Add Admin Menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Mobility Trailblazers', 'mobility-trailblazers'),
            __('Trailblazers', 'mobility-trailblazers'),
            'manage_options',
            'mobility-trailblazers',
            array($this, 'admin_dashboard'),
            'dashicons-star-filled',
            6
        );
        
        add_submenu_page(
            'mobility-trailblazers',
            __('Dashboard', 'mobility-trailblazers'),
            __('Dashboard', 'mobility-trailblazers'),
            'manage_options',
            'mobility-trailblazers',
            array($this, 'admin_dashboard')
        );
        
        add_submenu_page(
            'mobility-trailblazers',
            __('Import Candidates', 'mobility-trailblazers'),
            __('Import Candidates', 'mobility-trailblazers'),
            'manage_options',
            'mobility-import',
            array($this, 'import_page')
        );
    }
    
    /**
     * Admin Dashboard
     */
    public function admin_dashboard() {
        $total_candidates = wp_count_posts('candidate')->publish;
        $total_jury = wp_count_posts('jury_member')->publish;
        
        // Count by stage
        $stages = get_terms('candidate_stage', array('hide_empty' => false));
        $stage_counts = array();
        foreach ($stages as $stage) {
            $stage_counts[$stage->name] = $stage->count;
        }
        
        // Count by category
        $categories = get_terms('candidate_category', array('hide_empty' => false));
        $category_counts = array();
        foreach ($categories as $category) {
            $category_counts[$category->name] = $category->count;
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . __('Mobility Trailblazers Dashboard', 'mobility-trailblazers') . '</h1>';
        
        echo '<div class="mobility-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">';
        
        echo '<div class="stat-box" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h3>' . __('Total Candidates', 'mobility-trailblazers') . '</h3>';
        echo '<div style="font-size: 36px; font-weight: bold; color: #0073aa;">' . $total_candidates . '</div>';
        echo '</div>';
        
        echo '<div class="stat-box" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h3>' . __('Jury Members', 'mobility-trailblazers') . '</h3>';
        echo '<div style="font-size: 36px; font-weight: bold; color: #00a32a;">' . $total_jury . '</div>';
        echo '</div>';
        
        echo '</div>';
        
        // Stage breakdown
        if (!empty($stage_counts)) {
            echo '<h2>' . __('Candidates by Stage', 'mobility-trailblazers') . '</h2>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Stage', 'mobility-trailblazers') . '</th><th>' . __('Count', 'mobility-trailblazers') . '</th></tr></thead><tbody>';
            foreach ($stage_counts as $stage => $count) {
                echo '<tr><td>' . esc_html($stage) . '</td><td>' . $count . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        
        // Category breakdown
        if (!empty($category_counts)) {
            echo '<h2>' . __('Candidates by Category', 'mobility-trailblazers') . '</h2>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Category', 'mobility-trailblazers') . '</th><th>' . __('Count', 'mobility-trailblazers') . '</th></tr></thead><tbody>';
            foreach ($category_counts as $category => $count) {
                echo '<tr><td>' . esc_html($category) . '</td><td>' . $count . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        
        // Shortcode usage examples
        echo '<h2>' . __('Usage Examples', 'mobility-trailblazers') . '</h2>';
        echo '<p>' . __('You can use these shortcodes to display different components on your pages:', 'mobility-trailblazers') . '</p>';
        
        echo '<div class="mt-shortcode-examples" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">';
        echo '<h4>' . __('Available Shortcodes:', 'mobility-trailblazers') . '</h4>';
        echo '<ul>';
        echo '<li><code>[mt_candidate_grid]</code> - ' . __('Display candidates in a grid layout', 'mobility-trailblazers') . '</li>';
        echo '<li><code>[mt_voting_interface]</code> - ' . __('Jury voting interface (jury members only)', 'mobility-trailblazers') . '</li>';
        echo '<li><code>[mt_jury_dashboard]</code> - ' . __('Jury member dashboard (jury members only)', 'mobility-trailblazers') . '</li>';
        echo '<li><code>[mt_voting_progress]</code> - ' . __('Display voting progress and statistics', 'mobility-trailblazers') . '</li>';
        echo '</ul>';
        
        echo '<h4>' . __('Example with Parameters:', 'mobility-trailblazers') . '</h4>';
        echo '<code>[mt_candidate_grid category="established" limit="6" show_voting="true"]</code>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Import Page
     */
    public function import_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Import Candidates', 'mobility-trailblazers') . '</h1>';
        echo '<p>' . __('CSV import functionality will be added in the next version.', 'mobility-trailblazers') . '</p>';
        echo '<p>' . sprintf(__('For now, you can add candidates manually via <a href="%s">Add New Candidate</a>', 'mobility-trailblazers'), admin_url('post-new.php?post_type=candidate')) . '</p>';
        echo '</div>';
    }
    
    /**
     * Enqueue Scripts (Frontend)
     */
    public function enqueue_scripts() {
        // Only enqueue if user has voting permissions or on candidate pages
        if (current_user_can('vote_on_candidates') || current_user_can('manage_voting_phases') || is_singular('candidate') || is_post_type_archive('candidate')) {
            
            wp_enqueue_script(
                'mt-voting-interface',
                MT_PLUGIN_URL . 'assets/js/voting-interface.js',
                array('jquery'),
                MT_PLUGIN_VERSION,
                true
            );
            
            wp_enqueue_style(
                'mt-voting-styles',
                MT_PLUGIN_URL . 'assets/css/voting-styles.css',
                array(),
                MT_PLUGIN_VERSION
            );
            
            wp_localize_script('mt-voting-interface', 'mtVotingData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url('mt/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'currentUser' => get_current_user_id(),
                'userCan' => array(
                    'vote' => current_user_can('vote_on_candidates'),
                    'manageVoting' => current_user_can('manage_voting_phases'),
                    'viewResults' => current_user_can('view_voting_results')
                )
            ));
        }
    }
    
    /**
     * NEW: Enqueue admin scripts with Chart.js for assignment interface
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on assignment interface pages
        if (strpos($hook, 'mobility-') !== false) {
            
            // Enqueue Chart.js from CDN for analytics
            wp_enqueue_script(
                'chartjs',
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
                array(),
                '3.9.1',
                true
            );
            
            // Enqueue assignment interface JS
            wp_enqueue_script(
                'mt-assignment-interface',
                MT_PLUGIN_URL . 'assets/js/assignment-interface.js',
                array('jquery', 'chartjs'), // Add chartjs as dependency
                MT_PLUGIN_VERSION,
                true
            );
            
            // Enqueue assignment interface CSS
            wp_enqueue_style(
                'mt-assignment-styles',
                MT_PLUGIN_URL . 'assets/css/assignment-interface.css',
                array(),
                MT_PLUGIN_VERSION
            );
            
            wp_localize_script('mt-assignment-interface', 'mtAssignment', array(
                'apiUrl' => rest_url('mt/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'adminNonce' => wp_create_nonce('mt_admin_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'currentUser' => get_current_user_id()
            ));
        }
    }
}

/**
 * Initialize Voting System
 */
class MobilityTrailblazersLoader {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'), 10);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize all components
     */
    public function init() {
        // Load core plugin functionality
        new MobilityTrailblazers();
        
        // Load voting system backend
        $this->load_voting_system();
        
        // Load assignment interface
        $this->load_assignment_interface();
    }
    
    /**
     * Load voting system backend
     */
    private function load_voting_system() {
        $voting_system_file = MT_PLUGIN_PATH . 'voting-system.php';
        
        if (file_exists($voting_system_file)) {
            require_once $voting_system_file;
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('Mobility Trailblazers: Voting system file not found!', 'mobility-trailblazers');
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Load assignment interface
     */
    private function load_assignment_interface() {
        $assignment_interface_file = MT_PLUGIN_PATH . 'jury-assignment-interface.php';
        
        if (file_exists($assignment_interface_file)) {
            require_once $assignment_interface_file;
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>';
                echo esc_html__('Mobility Trailblazers: Assignment interface file not found!', 'mobility-trailblazers');
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_database_tables();
        
        // Create default user roles
        $this->create_user_roles();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        error_log('Mobility Trailblazers plugin activated');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('mt_voting_deadline_reminder');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('Mobility Trailblazers plugin deactivated');
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Voting tables
        $tables = [
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mt_votes (
                id int(11) NOT NULL AUTO_INCREMENT,
                jury_member_id bigint(20) NOT NULL,
                candidate_id bigint(20) NOT NULL,
                stage enum('shortlist','semifinal','final') NOT NULL,
                pioneer_spirit tinyint(2) DEFAULT 5,
                innovation_degree tinyint(2) DEFAULT 5,
                implementation_power tinyint(2) DEFAULT 5,
                role_model_function tinyint(2) DEFAULT 5,
                total_score decimal(4,2) DEFAULT NULL,
                comments text,
                is_final boolean DEFAULT FALSE,
                voted_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_vote (jury_member_id, candidate_id, stage),
                KEY idx_stage (stage),
                KEY idx_candidate (candidate_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mt_voting_phases (
                id int(11) NOT NULL AUTO_INCREMENT,
                phase_name varchar(100) NOT NULL,
                stage enum('shortlist','semifinal','final') NOT NULL,
                start_date datetime NOT NULL,
                end_date datetime NOT NULL,
                is_active boolean DEFAULT FALSE,
                description text,
                settings text,
                PRIMARY KEY (id),
                KEY idx_stage (stage),
                KEY idx_active (is_active)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mt_jury_assignments (
                id int(11) NOT NULL AUTO_INCREMENT,
                jury_member_id bigint(20) NOT NULL,
                candidate_id bigint(20) NOT NULL,
                stage enum('shortlist','semifinal','final') NOT NULL,
                assigned_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_assignment (jury_member_id, candidate_id, stage),
                KEY idx_jury (jury_member_id),
                KEY idx_candidate (candidate_id)
            ) $charset_collate;"
        ];
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table_sql) {
            dbDelta($table_sql);
        }
        
        // Update database version
        update_option('mt_db_version', '1.0.0');
    }
    
    /**
     * Create custom user roles
     */
    private function create_user_roles() {
        // Create jury member role if it doesn't exist
        if (!get_role('jury_member')) {
            add_role('jury_member', __('Jury Member', 'mobility-trailblazers'), [
                'read' => true,
                'vote_on_candidates' => true,
                'view_assigned_candidates' => true,
                'edit_own_votes' => true
            ]);
        }
        
        // Add capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_capabilities = [
                'manage_voting_phases',
                'assign_candidates_to_jury',
                'view_all_votes',
                'manage_jury_members',
                'view_voting_reports',
                'export_voting_data'
            ];
            
            foreach ($admin_capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
    }
}

// Initialize the plugin
MobilityTrailblazersLoader::get_instance();

/**
 * Helper function to get plugin instance
 */
function mobility_trailblazers() {
    return MobilityTrailblazersLoader::get_instance();
}

/**
 * Plugin compatibility checks
 */
add_action('admin_init', function() {
    // Check WordPress version
    if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Mobility Trailblazers requires WordPress 5.0 or higher.', 'mobility-trailblazers');
            echo '</p></div>';
        });
        return;
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Mobility Trailblazers requires PHP 7.4 or higher.', 'mobility-trailblazers');
            echo '</p></div>';
        });
        return;
    }
});

/**
 * Add plugin action links
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=mobility-trailblazers') . '">' . 
                     esc_html__('Settings', 'mobility-trailblazers') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});