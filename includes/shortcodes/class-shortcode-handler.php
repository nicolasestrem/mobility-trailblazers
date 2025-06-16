<?php
/**
 * Shortcode Handler for Mobility Trailblazers
 * File: includes/shortcodes/class-shortcode-handler.php
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

namespace MobilityTrailblazers\Shortcodes;

use MobilityTrailblazers\Core\JuryMember;
use MobilityTrailblazers\Core\Candidate;
use MobilityTrailblazers\Core\Evaluation;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ShortcodeHandler
 * 
 * Handles all plugin shortcodes
 */
class ShortcodeHandler {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->register_shortcodes();
    }
    
    /**
     * Register all shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('mt_jury_dashboard', [$this, 'jury_dashboard_shortcode']);
        add_shortcode('mt_candidate_grid', [$this, 'candidate_grid_shortcode']);
        add_shortcode('mt_voting_form', [$this, 'voting_form_shortcode']);
        add_shortcode('mt_jury_members', [$this, 'jury_members_shortcode']);
        add_shortcode('mt_voting_results', [$this, 'voting_results_shortcode']);
    }
    
    /**
     * Jury Dashboard Shortcode
     */
    public function jury_dashboard_shortcode($atts) {
        $atts = shortcode_atts([
            'show_stats' => 'true',
            'show_assignments' => 'true',
            'show_progress' => 'true'
        ], $atts);
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }
        
        // Check if user is jury member
        $current_user_id = get_current_user_id();
        $jury_member = new JuryMember();
        
        if (!$jury_member->is_jury_member($current_user_id)) {
            return '<p class="mt-access-denied">' . __('This dashboard is only accessible to jury members.', 'mobility-trailblazers') . '</p>';
        }
        
        // Get jury member data
        $jury_member_id = $jury_member->get_jury_member_id_for_user($current_user_id);
        $assignments = $jury_member->get_assigned_candidates($jury_member_id);
        $evaluations = new Evaluation();
        
        // Calculate stats
        $total_assigned = count($assignments);
        $evaluated_count = 0;
        $pending_count = 0;
        
        foreach ($assignments as $candidate) {
            if ($evaluations->has_evaluated($current_user_id, $candidate->ID)) {
                $evaluated_count++;
            } else {
                $pending_count++;
            }
        }
        
        $completion_percentage = $total_assigned > 0 ? round(($evaluated_count / $total_assigned) * 100) : 0;
        
        // Start output buffering
        ob_start();
        
        // Include template or render inline
        $template_file = MT_PLUGIN_PATH . 'templates/shortcodes/jury-dashboard.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            $this->render_jury_dashboard(
                $atts,
                $assignments,
                $total_assigned,
                $evaluated_count,
                $pending_count,
                $completion_percentage,
                $current_user_id,
                $jury_member_id
            );
        }
        
        return ob_get_clean();
    }
    
    /**
     * Candidate Grid Shortcode
     */
    public function candidate_grid_shortcode($atts) {
        $atts = shortcode_atts([
            'category' => '',
            'status' => 'finalist',
            'limit' => 25,
            'columns' => 3,
            'show_voting' => 'true',
            'show_evaluation' => 'false',
            'orderby' => 'title',
            'order' => 'ASC'
        ], $atts);
        
        // Build query args
        $args = [
            'post_type' => 'mt_candidate',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order'])
        ];
        
        // Add taxonomy queries
        $tax_query = [];
        
        if (!empty($atts['category'])) {
            $tax_query[] = [
                'taxonomy' => 'mt_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['category'])
            ];
        }
        
        if (!empty($atts['status'])) {
            $tax_query[] = [
                'taxonomy' => 'mt_status',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['status'])
            ];
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        // Query candidates
        $candidates = new \WP_Query($args);
        
        if (!$candidates->have_posts()) {
            return '<p class="mt-no-candidates">' . __('No candidates found.', 'mobility-trailblazers') . '</p>';
        }
        
        // Start output buffering
        ob_start();
        
        // Include template or render inline
        $template_file = MT_PLUGIN_PATH . 'templates/shortcodes/candidate-grid.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            $this->render_candidate_grid($candidates, $atts);
        }
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Voting Form Shortcode
     */
    public function voting_form_shortcode($atts) {
        $atts = shortcode_atts([
            'candidate_id' => 0,
            'type' => 'public',
            'show_criteria' => 'true',
            'redirect_after' => ''
        ], $atts);
        
        // Validate candidate ID
        $candidate_id = intval($atts['candidate_id']);
        if (!$candidate_id) {
            return '<p class="mt-error">' . __('Please specify a candidate ID.', 'mobility-trailblazers') . '</p>';
        }
        
        // Verify candidate exists
        $candidate = get_post($candidate_id);
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            return '<p class="mt-error">' . __('Invalid candidate.', 'mobility-trailblazers') . '</p>';
        }
        
        // Check if jury voting requires login
        if ($atts['type'] === 'jury' && !is_user_logged_in()) {
            return $this->get_login_message();
        }
        
        // Start output buffering
        ob_start();
        
        // Include template or render inline
        $template_file = MT_PLUGIN_PATH . 'templates/shortcodes/voting-form.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            $this->render_voting_form($candidate, $atts);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Jury Members Shortcode
     */
    public function jury_members_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => -1,
            'show_bio' => 'true',
            'show_photo' => 'true',
            'columns' => 3,
            'category' => '',
            'orderby' => 'menu_order title',
            'order' => 'ASC'
        ], $atts);
        
        // Query jury members
        $args = [
            'post_type' => 'mt_jury',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order'])
        ];
        
        // Add category filter if specified
        if (!empty($atts['category'])) {
            $args['meta_query'] = [
                [
                    'key' => '_mt_jury_category',
                    'value' => sanitize_text_field($atts['category']),
                    'compare' => '='
                ]
            ];
        }
        
        $jury_members = new \WP_Query($args);
        
        if (!$jury_members->have_posts()) {
            return '<p class="mt-no-jury">' . __('No jury members found.', 'mobility-trailblazers') . '</p>';
        }
        
        // Start output buffering
        ob_start();
        
        // Include template or render inline
        $template_file = MT_PLUGIN_PATH . 'templates/shortcodes/jury-members.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            $this->render_jury_members($jury_members, $atts);
        }
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Voting Results Shortcode
     */
    public function voting_results_shortcode($atts) {
        $atts = shortcode_atts([
            'type' => 'jury',
            'limit' => 10,
            'category' => '',
            'show_scores' => 'true',
            'show_rank' => 'true',
            'min_votes' => 1
        ], $atts);
        
        global $wpdb;
        $evaluation = new Evaluation();
        
        // Get results based on type
        if ($atts['type'] === 'jury') {
            $results = $evaluation->get_top_candidates_by_score(
                intval($atts['limit']),
                $atts['category'],
                intval($atts['min_votes'])
            );
        } else {
            // Public voting results
            $results = $evaluation->get_public_voting_results(
                intval($atts['limit']),
                $atts['category']
            );
        }
        
        if (empty($results)) {
            return '<p class="mt-no-results">' . __('No voting results available yet.', 'mobility-trailblazers') . '</p>';
        }
        
        // Start output buffering
        ob_start();
        
        // Include template or render inline
        $template_file = MT_PLUGIN_PATH . 'templates/shortcodes/voting-results.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            $this->render_voting_results($results, $atts);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Get login message
     */
    private function get_login_message() {
        $login_url = wp_login_url(get_permalink());
        return sprintf(
            '<div class="mt-login-required">
                <p>%s</p>
                <a href="%s" class="mt-login-button button">%s</a>
            </div>',
            __('Please log in to access this content.', 'mobility-trailblazers'),
            esc_url($login_url),
            __('Log In', 'mobility-trailblazers')
        );
    }
    
    /**
     * Render jury dashboard inline
     */
    private function render_jury_dashboard($atts, $assignments, $total, $evaluated, $pending, $percentage, $user_id, $jury_id) {
        ?>
        <div class="mt-jury-dashboard">
            <?php if ($atts['show_stats'] === 'true'): ?>
            <div class="mt-dashboard-stats">
                <div class="mt-stat-card">
                    <h3><?php _e('Assigned', 'mobility-trailblazers'); ?></h3>
                    <div class="mt-stat-number"><?php echo $total; ?></div>
                    <p><?php _e('Total candidates', 'mobility-trailblazers'); ?></p>
                </div>
                
                <div class="mt-stat-card">
                    <h3><?php _e('Evaluated', 'mobility-trailblazers'); ?></h3>
                    <div class="mt-stat-number"><?php echo $evaluated; ?></div>
                    <p><?php _e('Completed evaluations', 'mobility-trailblazers'); ?></p>
                </div>
                
                <div class="mt-stat-card">
                    <h3><?php _e('Pending', 'mobility-trailblazers'); ?></h3>
                    <div class="mt-stat-number"><?php echo $pending; ?></div>
                    <p><?php _e('Awaiting evaluation', 'mobility-trailblazers'); ?></p>
                </div>
                
                <?php if ($atts['show_progress'] === 'true'): ?>
                <div class="mt-stat-card">
                    <h3><?php _e('Progress', 'mobility-trailblazers'); ?></h3>
                    <div class="mt-stat-number"><?php echo $percentage; ?>%</div>
                    <div class="mt-progress-bar">
                        <div class="mt-progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_assignments'] === 'true' && !empty($assignments)): ?>
            <div class="mt-assignments-section">
                <h2><?php _e('Your Assigned Candidates', 'mobility-trailblazers'); ?></h2>
                <div class="mt-candidate-list">
                    <?php foreach ($assignments as $candidate): 
                        $evaluation = new Evaluation();
                        $evaluated = $evaluation->has_evaluated($user_id, $candidate->ID);
                    ?>
                    <div class="mt-candidate-item <?php echo $evaluated ? 'evaluated' : 'pending'; ?>">
                        <h3><?php echo esc_html($candidate->post_title); ?></h3>
                        <div class="mt-candidate-meta">
                            <?php 
                            $company = get_post_meta($candidate->ID, '_mt_company', true);
                            if ($company): 
                            ?>
                            <span class="mt-company"><?php echo esc_html($company); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-candidate-actions">
                            <a href="<?php echo get_permalink($candidate->ID); ?>" class="button button-secondary">
                                <?php _e('View Details', 'mobility-trailblazers'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=mt-evaluate&candidate=' . $candidate->ID); ?>" class="button button-primary">
                                <?php echo $evaluated ? __('Edit Evaluation', 'mobility-trailblazers') : __('Evaluate', 'mobility-trailblazers'); ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render candidate grid inline
     */
    private function render_candidate_grid($candidates, $atts) {
        $columns = intval($atts['columns']);
        ?>
        <div class="mt-candidate-grid" style="grid-template-columns: repeat(<?php echo $columns; ?>, 1fr);">
            <?php while ($candidates->have_posts()): $candidates->the_post(); ?>
            <div class="mt-candidate-card">
                <?php if (has_post_thumbnail()): ?>
                <div class="mt-candidate-image">
                    <?php the_post_thumbnail('medium'); ?>
                </div>
                <?php endif; ?>
                
                <div class="mt-candidate-content">
                    <h3><?php the_title(); ?></h3>
                    
                    <?php
                    $company = get_post_meta(get_the_ID(), '_mt_company', true);
                    $position = get_post_meta(get_the_ID(), '_mt_position', true);
                    ?>
                    
                    <?php if ($position): ?>
                    <p class="mt-position"><?php echo esc_html($position); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($company): ?>
                    <p class="mt-company"><?php echo esc_html($company); ?></p>
                    <?php endif; ?>
                    
                    <div class="mt-candidate-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    
                    <?php if ($atts['show_voting'] === 'true'): ?>
                    <div class="mt-voting-section">
                        <?php echo do_shortcode('[mt_voting_form candidate_id="' . get_the_ID() . '"]'); ?>
                    </div>
                    <?php endif; ?>
                    
                    <a href="<?php the_permalink(); ?>" class="mt-read-more button">
                        <?php _e('Read More', 'mobility-trailblazers'); ?>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php
    }
    
    /**
     * Render voting form inline
     */
    private function render_voting_form($candidate, $atts) {
        ?>
        <div class="mt-voting-form" data-candidate-id="<?php echo $candidate->ID; ?>">
            <h3><?php printf(__('Vote for %s', 'mobility-trailblazers'), esc_html($candidate->post_title)); ?></h3>
            
            <form id="mt-voting-form-<?php echo $candidate->ID; ?>" class="mt-vote-form">
                <?php wp_nonce_field('mt_vote_nonce', 'mt_vote_nonce'); ?>
                <input type="hidden" name="candidate_id" value="<?php echo $candidate->ID; ?>">
                <input type="hidden" name="vote_type" value="<?php echo esc_attr($atts['type']); ?>">
                
                <?php if ($atts['type'] === 'public'): ?>
                <p>
                    <label for="voter_email"><?php _e('Your Email:', 'mobility-trailblazers'); ?></label>
                    <input type="email" id="voter_email" name="voter_email" required>
                </p>
                <?php endif; ?>
                
                <?php if ($atts['show_criteria'] === 'true' && $atts['type'] === 'jury'): ?>
                <div class="mt-voting-criteria">
                    <?php
                    $criteria = [
                        'innovation' => __('Innovation', 'mobility-trailblazers'),
                        'impact' => __('Impact', 'mobility-trailblazers'),
                        'implementation' => __('Implementation', 'mobility-trailblazers'),
                        'sustainability' => __('Sustainability', 'mobility-trailblazers'),
                        'scalability' => __('Scalability', 'mobility-trailblazers')
                    ];
                    
                    foreach ($criteria as $key => $label):
                    ?>
                    <div class="mt-criterion">
                        <label for="<?php echo $key; ?>_score"><?php echo $label; ?>:</label>
                        <select name="<?php echo $key; ?>_score" id="<?php echo $key; ?>_score" required>
                            <option value="">-- Select --</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-comments">
                        <label for="comments"><?php _e('Comments (optional):', 'mobility-trailblazers'); ?></label>
                        <textarea name="comments" id="comments" rows="4"></textarea>
                    </div>
                </div>
                <?php endif; ?>
                
                <p class="mt-submit">
                    <button type="submit" class="button button-primary">
                        <?php _e('Submit Vote', 'mobility-trailblazers'); ?>
                    </button>
                </p>
            </form>
            
            <div class="mt-vote-message"></div>
        </div>
        <?php
    }
    
    /**
     * Render jury members inline
     */
    private function render_jury_members($jury_members, $atts) {
        $columns = intval($atts['columns']);
        ?>
        <div class="mt-jury-grid" style="grid-template-columns: repeat(<?php echo $columns; ?>, 1fr);">
            <?php while ($jury_members->have_posts()): $jury_members->the_post(); ?>
            <?php
            $is_president = get_post_meta(get_the_ID(), '_mt_jury_is_president', true);
            $is_vice_president = get_post_meta(get_the_ID(), '_mt_jury_is_vice_president', true);
            $company = get_post_meta(get_the_ID(), '_mt_jury_company', true);
            $position = get_post_meta(get_the_ID(), '_mt_jury_position', true);
            $expertise = get_post_meta(get_the_ID(), '_mt_jury_expertise', true);
            ?>
            
            <div class="mt-jury-card <?php echo $is_president ? 'president' : ($is_vice_president ? 'vice-president' : ''); ?>">
                <?php if ($atts['show_photo'] === 'true' && has_post_thumbnail()): ?>
                <div class="mt-jury-image">
                    <?php the_post_thumbnail('medium'); ?>
                </div>
                <?php endif; ?>
                
                <div class="mt-jury-content">
                    <?php if ($is_president): ?>
                    <span class="mt-jury-role president"><?php _e('President', 'mobility-trailblazers'); ?></span>
                    <?php elseif ($is_vice_president): ?>
                    <span class="mt-jury-role vice-president"><?php _e('Vice President', 'mobility-trailblazers'); ?></span>
                    <?php endif; ?>
                    
                    <h3><?php the_title(); ?></h3>
                    
                    <?php if ($position): ?>
                    <p class="mt-jury-position"><?php echo esc_html($position); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($company): ?>
                    <p class="mt-jury-company"><?php echo esc_html($company); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($expertise): ?>
                    <p class="mt-jury-expertise"><?php echo esc_html($expertise); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($atts['show_bio'] === 'true'): ?>
                    <div class="mt-jury-bio">
                        <?php the_excerpt(); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php
    }
    
    /**
     * Render voting results inline
     */
    private function render_voting_results($results, $atts) {
        ?>
        <div class="mt-voting-results">
            <h3><?php _e('Voting Results', 'mobility-trailblazers'); ?></h3>
            
            <ol class="mt-results-list">
                <?php 
                $rank = 1;
                foreach ($results as $result): 
                ?>
                <li class="mt-result-item">
                    <?php if ($atts['show_rank'] === 'true'): ?>
                    <span class="mt-rank"><?php echo $rank; ?></span>
                    <?php endif; ?>
                    
                    <span class="mt-candidate-name"><?php echo esc_html($result->candidate_name); ?></span>
                    
                    <?php if ($atts['show_scores'] === 'true'): ?>
                    <span class="mt-result-score">
                        <?php 
                        if ($atts['type'] === 'jury') {
                            echo sprintf(
                                __('%s/50 (%d evaluations)', 'mobility-trailblazers'),
                                number_format($result->avg_score, 1),
                                intval($result->evaluation_count)
                            );
                        } else {
                            echo sprintf(
                                __('%d votes', 'mobility-trailblazers'),
                                intval($result->vote_count)
                            );
                        }
                        ?>
                    </span>
                    <?php endif; ?>
                </li>
                <?php 
                $rank++;
                endforeach; 
                ?>
            </ol>
        </div>
        <?php
    }
}