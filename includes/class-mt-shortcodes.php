<?php
/**
 * Shortcodes Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Shortcodes
 * Handles all plugin shortcodes
 */
class MT_Shortcodes {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        $this->register_shortcodes();
    }
    
    /**
     * Register all shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('mt_voting_form', array($this, 'voting_form_shortcode'));
        add_shortcode('mt_candidate_grid', array($this, 'candidate_grid_shortcode'));
        add_shortcode('mt_jury_members', array($this, 'jury_members_shortcode'));
        add_shortcode('mt_voting_results', array($this, 'voting_results_shortcode'));
        add_shortcode('mt_jury_dashboard', array($this, 'jury_dashboard_shortcode'));
    }
    
    /**
     * Voting form shortcode
     */
    public function voting_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'phase' => 'shortlist'
        ), $atts);
        
        // Check if user is logged in and is a jury member
        if (!is_user_logged_in() || !MT_Roles::is_jury_member()) {
            return '<p>' . __('You must be a logged-in jury member to vote.', 'mobility-trailblazers') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="mt-voting-form">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('mt_jury_vote', 'mt_vote_nonce'); ?>
                <input type="hidden" name="action" value="mt_jury_vote">
                
                <!-- Voting form content will be loaded via AJAX -->
                <div id="mt-voting-candidates">
                    <p><?php _e('Loading candidates...', 'mobility-trailblazers'); ?></p>
                </div>
                
                <button type="submit" class="button button-primary">
                    <?php _e('Submit Votes', 'mobility-trailblazers'); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Candidate grid shortcode
     */
    public function candidate_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'phase' => '',
            'columns' => 3,
            'limit' => -1,
            'show_votes' => 'no'
        ), $atts);
        
        $args = array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish'
        );
        
        // Add taxonomy filters
        $tax_query = array();
        
        if (!empty($atts['category'])) {
            $tax_query[] = array(
                'taxonomy' => 'mt_category',
                'field' => 'slug',
                'terms' => $atts['category']
            );
        }
        
        if (!empty($atts['phase'])) {
            $tax_query[] = array(
                'taxonomy' => 'mt_phase',
                'field' => 'slug',
                'terms' => $atts['phase']
            );
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        $candidates = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="mt-candidate-grid columns-<?php echo esc_attr($atts['columns']); ?>">
            <?php if ($candidates->have_posts()) : ?>
                <?php while ($candidates->have_posts()) : $candidates->the_post(); ?>
                    <div class="mt-candidate-card">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="candidate-thumbnail">
                                <?php the_post_thumbnail('candidate-thumbnail'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h3><?php the_title(); ?></h3>
                        
                        <div class="candidate-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <?php if ($atts['show_votes'] === 'yes' && current_user_can('mt_view_all_evaluations')) : ?>
                            <div class="candidate-votes">
                                <?php
                                global $wpdb;
                                $votes = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE candidate_id = %d",
                                    get_the_ID()
                                ));
                                printf(__('Votes: %d', 'mobility-trailblazers'), $votes);
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="<?php the_permalink(); ?>" class="button">
                            <?php _e('View Details', 'mobility-trailblazers'); ?>
                        </a>
                    </div>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <p><?php _e('No candidates found.', 'mobility-trailblazers'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Jury members shortcode
     */
    public function jury_members_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 4,
            'show_bio' => 'yes'
        ), $atts);
        
        $args = array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $jury_members = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="mt-jury-grid columns-<?php echo esc_attr($atts['columns']); ?>">
            <?php if ($jury_members->have_posts()) : ?>
                <?php while ($jury_members->have_posts()) : $jury_members->the_post(); ?>
                    <div class="mt-jury-member">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="jury-photo">
                                <?php the_post_thumbnail('thumbnail'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h4><?php the_title(); ?></h4>
                        
                        <?php
                        $organization = get_post_meta(get_the_ID(), 'organization', true);
                        $position = get_post_meta(get_the_ID(), 'position', true);
                        ?>
                        
                        <?php if ($position) : ?>
                            <p class="jury-position"><?php echo esc_html($position); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($organization) : ?>
                            <p class="jury-organization"><?php echo esc_html($organization); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_bio'] === 'yes' && has_excerpt()) : ?>
                            <div class="jury-bio">
                                <?php the_excerpt(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <p><?php _e('No jury members found.', 'mobility-trailblazers'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Voting results shortcode
     */
    public function voting_results_shortcode($atts) {
        // Check permissions
        if (!current_user_can('mt_view_all_evaluations')) {
            return '<p>' . __('You do not have permission to view voting results.', 'mobility-trailblazers') . '</p>';
        }
        
        $atts = shortcode_atts(array(
            'phase' => 'current',
            'category' => '',
            'limit' => 10
        ), $atts);
        
        global $wpdb;
        
        // Get top candidates by votes
        $query = "SELECT c.ID, c.post_title, COUNT(v.id) as vote_count, AVG(v.rating) as avg_rating
                  FROM {$wpdb->posts} c
                  LEFT JOIN {$wpdb->prefix}mt_votes v ON c.ID = v.candidate_id
                  WHERE c.post_type = 'mt_candidate' AND c.post_status = 'publish'
                  GROUP BY c.ID
                  ORDER BY vote_count DESC, avg_rating DESC
                  LIMIT %d";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $atts['limit']));
        
        ob_start();
        ?>
        <div class="mt-voting-results">
            <h3><?php _e('Voting Results', 'mobility-trailblazers'); ?></h3>
            
            <?php if ($results) : ?>
                <table class="mt-results-table">
                    <thead>
                        <tr>
                            <th><?php _e('Rank', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Votes', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Average Rating', 'mobility-trailblazers'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; ?>
                        <?php foreach ($results as $result) : ?>
                            <tr>
                                <td><?php echo $rank++; ?></td>
                                <td>
                                    <a href="<?php echo get_permalink($result->ID); ?>">
                                        <?php echo esc_html($result->post_title); ?>
                                    </a>
                                </td>
                                <td><?php echo intval($result->vote_count); ?></td>
                                <td><?php echo number_format($result->avg_rating, 2); ?>/10</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No voting results available yet.', 'mobility-trailblazers'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Jury dashboard shortcode
     */
    public function jury_dashboard_shortcode($atts) {
        // Check if user is logged in and is a jury member
        if (!is_user_logged_in() || !MT_Roles::is_jury_member()) {
            return '<p>' . __('You must be a logged-in jury member to access the dashboard.', 'mobility-trailblazers') . '</p>';
        }
        
        // Get jury member data
        $user_id = get_current_user_id();
        $jury_member = $this->get_jury_member_for_user($user_id);
        
        if (!$jury_member) {
            return '<p>' . __('Your jury member profile could not be found. Please contact the administrator.', 'mobility-trailblazers') . '</p>';
        }
        
        ob_start();
        include MT_PLUGIN_PATH . 'templates/jury-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * Get jury member post for a user
     */
    private function get_jury_member_for_user($user_id) {
        $args = array(
            'post_type' => 'mt_jury',
            'meta_key' => 'user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1,
            'post_status' => 'any'
        );
        
        $jury_members = get_posts($args);
        return !empty($jury_members) ? $jury_members[0] : null;
    }
} 