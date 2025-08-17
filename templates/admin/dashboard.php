<?php
/**
 * Admin Dashboard Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mt-admin-dashboard">
    <h1><?php _e('Mobility Trailblazers Dashboard', 'mobility-trailblazers'); ?></h1>
    
    <div class="mt-dashboard-header">
        <h2><?php _e('Overview', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Welcome to the Mobility Trailblazers award management system.', 'mobility-trailblazers'); ?></p>
    </div>
    
    <!-- Statistics -->
    <div class="mt-stats-row">
        <div class="mt-stat-box">
            <h3><?php echo esc_html($eval_stats['total']); ?></h3>
            <p><?php _e('Total Evaluations', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-box">
            <h3><?php echo esc_html($eval_stats['completed']); ?></h3>
            <p><?php _e('Completed', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-box">
            <h3><?php echo esc_html($eval_stats['drafts']); ?></h3>
            <p><?php _e('Drafts', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-box">
            <h3><?php echo esc_html(number_format($eval_stats['average_score'], 1)); ?></h3>
            <p><?php _e('Average Score', 'mobility-trailblazers'); ?></p>
        </div>
    </div>
    
    <!-- Average Scores by Criteria -->
    <?php if (!empty($eval_stats['by_criteria'])) : ?>
    <div class="mt-chart-container">
        <h3><?php _e('Average Scores by Criteria', 'mobility-trailblazers'); ?></h3>
        <canvas id="mt-criteria-chart" class="mt-chart-canvas"></canvas>
    </div>
    <?php endif; ?>
    
    <!-- Recent Evaluations -->
    <?php if (!empty($recent_evaluations)) : ?>
    <div class="mt-admin-table">
        <h3><?php _e('Recent Evaluations', 'mobility-trailblazers'); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Total Score', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Date', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_evaluations as $evaluation) : 
                    $jury_member = get_post($evaluation->jury_member_id);
                    $candidate = get_post($evaluation->candidate_id);
                ?>
                <tr>
                    <td><?php echo $jury_member ? esc_html($jury_member->post_title) : __('Unknown', 'mobility-trailblazers'); ?></td>
                    <td><?php echo $candidate ? esc_html($candidate->post_title) : __('Unknown', 'mobility-trailblazers'); ?></td>
                    <td><?php echo esc_html($evaluation->total_score); ?></td>
                    <td>
                        <span class="mt-status mt-status-<?php echo esc_attr($evaluation->status); ?>">
                            <?php echo esc_html(ucfirst($evaluation->status)); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($evaluation->updated_at))); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Assignment Statistics -->
    <div class="mt-admin-table">
        <h3><?php _e('Assignment Overview', 'mobility-trailblazers'); ?></h3>
        <div class="mt-stats-row">
            <div class="mt-stat-box">
                <h3><?php echo esc_html($assign_stats['total_assignments']); ?></h3>
                <p><?php _e('Total Assignments', 'mobility-trailblazers'); ?></p>
            </div>
            <div class="mt-stat-box">
                <h3><?php echo esc_html($assign_stats['assigned_candidates']); ?></h3>
                <p><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></p>
            </div>
            <div class="mt-stat-box">
                <h3><?php echo esc_html($assign_stats['assigned_jury_members']); ?></h3>
                <p><?php _e('Active Jury Members', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($eval_stats['by_criteria'])) : ?>
<script>
jQuery(document).ready(function($) {
    // Criteria chart
    var ctx = document.getElementById('mt-criteria-chart').getContext('2d');
    var criteriaChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                '<?php _e("Courage & Pioneer Spirit", "mobility-trailblazers"); ?>',
                '<?php _e("Innovation Degree", "mobility-trailblazers"); ?>',
                '<?php _e("Implementation & Impact", "mobility-trailblazers"); ?>',
                '<?php _e("Mobility Transformation", "mobility-trailblazers"); ?>',
                '<?php _e("Role Model & Visibility", "mobility-trailblazers"); ?>'
            ],
            datasets: [{
                label: '<?php _e("Average Score", "mobility-trailblazers"); ?>',
                data: [
                    <?php echo esc_js($eval_stats['by_criteria']['courage']); ?>,
                    <?php echo esc_js($eval_stats['by_criteria']['innovation']); ?>,
                    <?php echo esc_js($eval_stats['by_criteria']['implementation']); ?>,
                    <?php echo esc_js($eval_stats['by_criteria']['relevance']); ?>,
                    <?php echo esc_js($eval_stats['by_criteria']['visibility']); ?>
                ],
                backgroundColor: 'rgba(102, 126, 234, 0.5)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10
                }
            }
        }
    });
});
</script>
<?php endif; ?> 