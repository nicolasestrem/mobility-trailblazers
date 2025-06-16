<?php
/**
 * Jury Dashboard Template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Ensure required objects are available
if (!isset($this->jury_member) || !isset($this->statistics) || !isset($this->candidate)) {
    return '<p>' . __('Error: Required components are not properly initialized.', 'mobility-trailblazers') . '</p>';
}

// Get current user
$current_user = wp_get_current_user();
if (!$current_user->exists()) {
    return '<p>' . __('Please log in to view the jury dashboard.', 'mobility-trailblazers') . '</p>';
}

// Get jury member ID
$jury_member_id = $this->jury_member->get_jury_member_id_for_user($current_user->ID);
if (!$jury_member_id) {
    return '<p>' . __('You do not have jury member access.', 'mobility-trailblazers') . '</p>';
}

// Get assigned candidates
$assigned_candidates = $this->jury_member->get_assigned_candidates($jury_member_id);

// Get evaluation progress
$progress = $this->statistics->get_evaluation_progress();

// Get jury member stats
$stats = $this->statistics->get_jury_member_stats($current_user->ID);

// Get top candidates
$top_candidates = $this->statistics->get_top_candidates(5);

// Get categories
$categories = get_terms(array(
    'taxonomy' => 'candidate_category',
    'hide_empty' => true
));

// Get rounds
$rounds = get_terms(array(
    'taxonomy' => 'vote_round',
    'hide_empty' => true
));

// Get statuses
$statuses = get_terms(array(
    'taxonomy' => 'candidate_status',
    'hide_empty' => true
));

// Get current filters
$current_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$current_round = isset($_GET['round']) ? sanitize_text_field($_GET['round']) : '';
$current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$current_search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$current_sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : '';
$current_page = isset($_GET['page']) ? absint($_GET['page']) : 1;

// Get candidates based on filters
$candidates = $this->candidate->get_all_candidates(array(
    'category' => $current_category,
    'round' => $current_round,
    'status' => $current_status,
    'search' => $current_search,
    'sort' => $current_sort,
    'page' => $current_page,
    'per_page' => $atts['items_per_page']
));

// Get total pages
$total_pages = ceil(count($candidates) / $atts['items_per_page']);

// Start output
?>

<div class="mt-jury-dashboard">
    <?php if ($atts['show_stats']): ?>
    <div class="mt-dashboard-stats">
        <h2><?php _e('Your Statistics', 'mobility-trailblazers'); ?></h2>
        <div class="mt-stats-grid">
            <div class="mt-stat-box">
                <h3><?php _e('Total Votes', 'mobility-trailblazers'); ?></h3>
                <p class="mt-stat-value"><?php echo esc_html($stats['total_votes']); ?></p>
            </div>
            <div class="mt-stat-box">
                <h3><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></h3>
                <p class="mt-stat-value"><?php echo esc_html($stats['assigned_candidates']); ?></p>
            </div>
            <div class="mt-stat-box">
                <h3><?php _e('Average Score', 'mobility-trailblazers'); ?></h3>
                <p class="mt-stat-value"><?php echo esc_html(number_format($stats['average_score'], 1)); ?></p>
            </div>
            <div class="mt-stat-box">
                <h3><?php _e('Evaluation Progress', 'mobility-trailblazers'); ?></h3>
                <p class="mt-stat-value"><?php echo esc_html($progress['percentage']); ?>%</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($atts['show_assignments']): ?>
    <div class="mt-assigned-candidates">
        <h2><?php _e('Your Assigned Candidates', 'mobility-trailblazers'); ?></h2>
        <?php if (!empty($assigned_candidates)): ?>
            <div class="mt-candidates-grid">
                <?php foreach ($assigned_candidates as $candidate): ?>
                    <div class="mt-candidate-card">
                        <h3><?php echo esc_html($candidate->post_title); ?></h3>
                        <p class="mt-candidate-category">
                            <?php
                            $categories = get_the_terms($candidate->ID, 'candidate_category');
                            if ($categories && !is_wp_error($categories)) {
                                echo esc_html($categories[0]->name);
                            }
                            ?>
                        </p>
                        <div class="mt-candidate-actions">
                            <a href="<?php echo esc_url(get_permalink($candidate->ID)); ?>" class="button">
                                <?php _e('View Details', 'mobility-trailblazers'); ?>
                            </a>
                            <a href="<?php echo esc_url(add_query_arg('candidate_id', $candidate->ID, get_permalink())); ?>" class="button">
                                <?php _e('Evaluate', 'mobility-trailblazers'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p><?php _e('No candidates have been assigned to you yet.', 'mobility-trailblazers'); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($atts['show_evaluations']): ?>
    <div class="mt-evaluation-progress">
        <h2><?php _e('Evaluation Progress', 'mobility-trailblazers'); ?></h2>
        <div class="mt-progress-bar">
            <div class="mt-progress-fill" style="width: <?php echo esc_attr($progress['percentage']); ?>%"></div>
        </div>
        <p class="mt-progress-text">
            <?php
            printf(
                __('%d of %d evaluations completed (%d%%)', 'mobility-trailblazers'),
                $progress['completed_votes'],
                $progress['total_votes'],
                $progress['percentage']
            );
            ?>
        </p>
    </div>
    <?php endif; ?>

    <?php if ($atts['show_public_voting']): ?>
    <div class="mt-public-voting">
        <h2><?php _e('Public Voting Results', 'mobility-trailblazers'); ?></h2>
        <?php if (!empty($top_candidates)): ?>
            <div class="mt-top-candidates">
                <?php foreach ($top_candidates as $candidate): ?>
                    <div class="mt-candidate-card">
                        <h3><?php echo esc_html($candidate->post_title); ?></h3>
                        <p class="mt-candidate-score">
                            <?php
                            printf(
                                __('Score: %s (%d votes)', 'mobility-trailblazers'),
                                number_format($candidate->average_score, 1),
                                $candidate->vote_count
                            );
                            ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p><?php _e('No public voting results available yet.', 'mobility-trailblazers'); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($atts['show_round_selector'] || $atts['show_category_filter'] || $atts['show_search'] || $atts['show_sort']): ?>
    <div class="mt-filters">
        <form method="get" class="mt-filter-form">
            <?php if ($atts['show_round_selector']): ?>
            <div class="mt-filter-group">
                <label for="round"><?php _e('Round', 'mobility-trailblazers'); ?></label>
                <select name="round" id="round">
                    <option value=""><?php _e('All Rounds', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($rounds as $round): ?>
                        <option value="<?php echo esc_attr($round->slug); ?>" <?php selected($current_round, $round->slug); ?>>
                            <?php echo esc_html($round->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($atts['show_category_filter']): ?>
            <div class="mt-filter-group">
                <label for="category"><?php _e('Category', 'mobility-trailblazers'); ?></label>
                <select name="category" id="category">
                    <option value=""><?php _e('All Categories', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($current_category, $category->slug); ?>>
                            <?php echo esc_html($category->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($atts['show_search']): ?>
            <div class="mt-filter-group">
                <label for="search"><?php _e('Search', 'mobility-trailblazers'); ?></label>
                <input type="text" name="search" id="search" value="<?php echo esc_attr($current_search); ?>" placeholder="<?php esc_attr_e('Search candidates...', 'mobility-trailblazers'); ?>">
            </div>
            <?php endif; ?>

            <?php if ($atts['show_sort']): ?>
            <div class="mt-filter-group">
                <label for="sort"><?php _e('Sort By', 'mobility-trailblazers'); ?></label>
                <select name="sort" id="sort">
                    <option value="date_desc" <?php selected($current_sort, 'date_desc'); ?>><?php _e('Newest First', 'mobility-trailblazers'); ?></option>
                    <option value="date_asc" <?php selected($current_sort, 'date_asc'); ?>><?php _e('Oldest First', 'mobility-trailblazers'); ?></option>
                    <option value="title_asc" <?php selected($current_sort, 'title_asc'); ?>><?php _e('Title A-Z', 'mobility-trailblazers'); ?></option>
                    <option value="title_desc" <?php selected($current_sort, 'title_desc'); ?>><?php _e('Title Z-A', 'mobility-trailblazers'); ?></option>
                    <option value="score_desc" <?php selected($current_sort, 'score_desc'); ?>><?php _e('Highest Score', 'mobility-trailblazers'); ?></option>
                    <option value="score_asc" <?php selected($current_sort, 'score_asc'); ?>><?php _e('Lowest Score', 'mobility-trailblazers'); ?></option>
                </select>
            </div>
            <?php endif; ?>

            <div class="mt-filter-actions">
                <button type="submit" class="button"><?php _e('Apply Filters', 'mobility-trailblazers'); ?></button>
                <a href="<?php echo esc_url(remove_query_arg(array('category', 'round', 'status', 'search', 'sort', 'page'))); ?>" class="button">
                    <?php _e('Reset Filters', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="mt-candidates-list">
        <?php if (!empty($candidates)): ?>
            <div class="mt-candidates-grid">
                <?php foreach ($candidates as $candidate): ?>
                    <div class="mt-candidate-card">
                        <h3><?php echo esc_html($candidate->post_title); ?></h3>
                        <p class="mt-candidate-category">
                            <?php
                            $categories = get_the_terms($candidate->ID, 'candidate_category');
                            if ($categories && !is_wp_error($categories)) {
                                echo esc_html($categories[0]->name);
                            }
                            ?>
                        </p>
                        <div class="mt-candidate-actions">
                            <a href="<?php echo esc_url(get_permalink($candidate->ID)); ?>" class="button">
                                <?php _e('View Details', 'mobility-trailblazers'); ?>
                            </a>
                            <a href="<?php echo esc_url(add_query_arg('candidate_id', $candidate->ID, get_permalink())); ?>" class="button">
                                <?php _e('Evaluate', 'mobility-trailblazers'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($atts['show_pagination'] && $total_pages > 1): ?>
            <div class="mt-pagination">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('page', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo; Previous', 'mobility-trailblazers'),
                    'next_text' => __('Next &raquo;', 'mobility-trailblazers'),
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <p><?php _e('No candidates found matching your criteria.', 'mobility-trailblazers'); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
/* Jury Dashboard Styles */
.mt-jury-dashboard {
    max-width: 1200px;
    margin: 0 auto;
}

.mt-dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.mt-stat-card {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s ease;
}

.mt-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.mt-stat-card .mt-stat-icon {
    font-size: 36px;
    color: #0073aa;
    margin-bottom: 15px;
}

.mt-stat-card h3 {
    margin: 0 0 10px;
    font-size: 18px;
    color: #23282d;
}

.mt-stat-number {
    font-size: 36px;
    font-weight: 700;
    color: #0073aa;
    margin: 10px 0;
}

.mt-stat-card p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.mt-progress-bar {
    width: 100%;
    height: 10px;
    background: #f0f0f1;
    border-radius: 5px;
    margin-top: 10px;
    overflow: hidden;
}

.mt-progress-fill {
    height: 100%;
    background: #0073aa;
    transition: width 0.3s ease;
}

/* Assignments Section */
.mt-assignments-section {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mt-assignments-section h2 {
    margin: 0 0 20px;
    color: #23282d;
}

.mt-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.mt-filter-btn {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: #fff;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mt-filter-btn:hover {
    background: #f0f0f1;
}

.mt-filter-btn.active {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
}

.mt-candidate-list {
    display: grid;
    gap: 20px;
}

.mt-candidate-item {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #ddd;
    transition: all 0.3s ease;
}

.mt-candidate-item.evaluated {
    border-left-color: #46b450;
}

.mt-candidate-item.pending {
    border-left-color: #ffb900;
}

.mt-candidate-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.mt-candidate-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.mt-candidate-header h3 {
    margin: 0;
    color: #23282d;
}

.mt-status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.mt-status-badge.evaluated {
    background: #d4edda;
    color: #155724;
}

.mt-status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.mt-candidate-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.mt-candidate-meta p {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 14px;
}

.mt-candidate-meta .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #999;
}

.mt-candidate-actions {
    display: flex;
    gap: 10px;
}

.mt-candidate-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.mt-candidate-actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.mt-evaluation-summary {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}

.mt-evaluation-summary h4 {
    margin: 0 0 10px;
    font-size: 14px;
    color: #666;
}

.mt-score-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mt-total-score {
    font-size: 24px;
    font-weight: 700;
    color: #46b450;
}

.mt-evaluation-date {
    font-size: 12px;
    color: #999;
}

.mt-no-assignments {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.mt-no-assignments p {
    font-size: 16px;
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .mt-dashboard-stats {
        grid-template-columns: 1fr;
    }
    
    .mt-candidate-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .mt-candidate-meta {
        flex-direction: column;
        gap: 8px;
    }
    
    .mt-candidate-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .mt-candidate-actions .button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('.mt-filter-btn').on('click', function() {
        var filter = $(this).data('filter');
        
        // Update active button
        $('.mt-filter-btn').removeClass('active');
        $(this).addClass('active');
        
        // Filter items
        if (filter === 'all') {
            $('.mt-candidate-item').show();
        } else {
            $('.mt-candidate-item').hide();
            $('.mt-candidate-item[data-status="' + filter + '"]').show();
        }
    });
});
</script>