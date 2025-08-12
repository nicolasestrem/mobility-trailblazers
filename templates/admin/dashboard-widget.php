<?php
/**
 * Dashboard Widget Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
$candidate_count = wp_count_posts('mt_candidate')->publish;
$jury_count = wp_count_posts('mt_jury_member')->publish;

// Get evaluation statistics using the same method as main dashboard
$evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
$eval_stats = $evaluation_repo->get_statistics();
$evaluation_count = $eval_stats['total'];

// Get recent candidates
$recent_candidates = get_posts(array(
    'post_type' => 'mt_candidate',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
));

// Get recent jury members
$recent_jury = get_posts(array(
    'post_type' => 'mt_jury_member',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
));

// Get recent evaluations using the same method as main dashboard
$recent_evaluations = $evaluation_repo->find_all([
    'limit' => 5,
    'orderby' => 'updated_at',
    'order' => 'DESC'
]);
?>

<div class="mt-dashboard-widget">
    <div class="mt-stats-grid">
        <div class="mt-stat-card">
            <h3><?php echo esc_html($candidate_count); ?></h3>
            <p><?php _e('Candidates', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-card">
            <h3><?php echo esc_html($jury_count); ?></h3>
            <p><?php _e('Jury Members', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-card">
            <h3><?php echo esc_html($evaluation_count); ?></h3>
            <p><?php _e('Evaluations', 'mobility-trailblazers'); ?></p>
        </div>
    </div>

    <div class="mt-recent-content">
        <div class="mt-recent-section">
            <h4><?php _e('Recent Candidates', 'mobility-trailblazers'); ?></h4>
            <?php if (!empty($recent_candidates)) : ?>
                <ul>
                    <?php foreach ($recent_candidates as $candidate) : ?>
                        <li>
                            <a href="<?php echo get_edit_post_link($candidate->ID); ?>">
                                <?php echo esc_html($candidate->post_title); ?>
                            </a>
                            <span class="mt-date"><?php echo get_the_date('', $candidate->ID); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php _e('No candidates yet.', 'mobility-trailblazers'); ?></p>
            <?php endif; ?>
        </div>

        <div class="mt-recent-section">
            <h4><?php _e('Recent Jury Members', 'mobility-trailblazers'); ?></h4>
            <?php if (!empty($recent_jury)) : ?>
                <ul>
                    <?php foreach ($recent_jury as $member) : ?>
                        <li>
                            <a href="<?php echo get_edit_post_link($member->ID); ?>">
                                <?php echo esc_html($member->post_title); ?>
                            </a>
                            <span class="mt-date"><?php echo get_the_date('', $member->ID); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php _e('No jury members yet.', 'mobility-trailblazers'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mt-recent-section">
            <h4><?php _e('Recent Evaluations', 'mobility-trailblazers'); ?></h4>
            <?php if (!empty($recent_evaluations)) : ?>
                <ul>
                    <?php foreach ($recent_evaluations as $evaluation) : 
                        $jury_member = get_post($evaluation->jury_member_id);
                        $candidate = get_post($evaluation->candidate_id);
                    ?>
                        <li>
                            <div>
                                <?php echo $jury_member ? esc_html($jury_member->post_title) : __('Unknown', 'mobility-trailblazers'); ?>
                                <span style="color: #666; font-size: 11px;"> → </span>
                                <?php echo $candidate ? esc_html($candidate->post_title) : __('Unknown', 'mobility-trailblazers'); ?>
                            </div>
                            <span class="mt-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($evaluation->updated_at))); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php _e('No evaluations yet.', 'mobility-trailblazers'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-quick-actions">
        <h4><?php _e('Quick Actions', 'mobility-trailblazers'); ?></h4>
        <div class="mt-action-buttons">
            <a href="<?php echo admin_url('post-new.php?post_type=mt_candidate'); ?>" class="button button-primary">
                <?php _e('Add Candidate', 'mobility-trailblazers'); ?>
            </a>
            <a href="<?php echo admin_url('post-new.php?post_type=mt_jury_member'); ?>" class="button button-secondary">
                <?php _e('Add Jury Member', 'mobility-trailblazers'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=mobility-trailblazers-diagnostics'); ?>" class="button button-secondary">
                <?php _e('Diagnostics', 'mobility-trailblazers'); ?>
            </a>
        </div>
    </div>
</div>

<style>
.mt-dashboard-widget {
    padding: 15px;
}

.mt-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.mt-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    text-align: center;
}

.mt-stat-card h3 {
    margin: 0 0 5px 0;
    font-size: 24px;
    color: #0073aa;
}

.mt-stat-card p {
    margin: 0;
    color: #666;
    font-size: 12px;
    text-transform: uppercase;
}

.mt-recent-content {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.mt-recent-section h4 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.mt-recent-section ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.mt-recent-section li {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.mt-recent-section li:last-child {
    border-bottom: none;
}

.mt-recent-section a {
    text-decoration: none;
    color: #0073aa;
}

.mt-recent-section a:hover {
    color: #005a87;
}

.mt-date {
    color: #666;
    font-size: 11px;
    margin-left: 10px;
}

.mt-quick-actions h4 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.mt-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.mt-action-buttons .button {
    margin: 0;
}
</style> 