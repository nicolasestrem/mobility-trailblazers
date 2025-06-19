<?php
/**
 * Evaluation Statistics Template
 *
 * @package MobilityTrailblazers
 * 
 * Available variables:
 * $stats - Statistics array
 * $atts - Shortcode attributes
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mt-evaluation-stats-wrapper">
    <?php if ($atts['type'] === 'overview'): ?>
        <div class="mt-stats-overview">
            <h3><?php _e('Evaluation Overview', 'mobility-trailblazers'); ?></h3>
            
            <div class="mt-stats-grid">
                <div class="mt-stat-card">
                    <div class="mt-stat-value"><?php echo esc_html($stats['total_candidates']); ?></div>
                    <div class="mt-stat-label"><?php _e('Total Candidates', 'mobility-trailblazers'); ?></div>
                </div>
                
                <div class="mt-stat-card">
                    <div class="mt-stat-value"><?php echo esc_html($stats['total_evaluations']); ?></div>
                    <div class="mt-stat-label"><?php _e('Total Evaluations', 'mobility-trailblazers'); ?></div>
                </div>
                
                <div class="mt-stat-card">
                    <div class="mt-stat-value"><?php echo esc_html($stats['completion_rate']); ?>%</div>
                    <div class="mt-stat-label"><?php _e('Completion Rate', 'mobility-trailblazers'); ?></div>
                </div>
                
                <div class="mt-stat-card">
                    <div class="mt-stat-value"><?php echo esc_html($stats['average_score']); ?></div>
                    <div class="mt-stat-label"><?php _e('Average Score', 'mobility-trailblazers'); ?></div>
                </div>
            </div>
            
            <?php if ($atts['show_chart'] === 'yes'): ?>
                <div class="mt-stats-chart">
                    <canvas id="mt-overview-chart"></canvas>
                </div>
            <?php endif; ?>
        </div>
        
    <?php elseif ($atts['type'] === 'category'): ?>
        <div class="mt-stats-category">
            <h3><?php _e('Statistics by Category', 'mobility-trailblazers'); ?></h3>
            
            <div class="mt-category-stats">
                <?php foreach ($stats['by_category'] as $category => $cat_stats): ?>
                    <div class="mt-category-stat-row">
                        <h4><?php echo esc_html($category); ?></h4>
                        <div class="mt-category-metrics">
                            <span class="mt-metric">
                                <strong><?php _e('Candidates:', 'mobility-trailblazers'); ?></strong>
                                <?php echo esc_html($cat_stats['candidates']); ?>
                            </span>
                            <span class="mt-metric">
                                <strong><?php _e('Evaluations:', 'mobility-trailblazers'); ?></strong>
                                <?php echo esc_html($cat_stats['evaluations']); ?>
                            </span>
                            <span class="mt-metric">
                                <strong><?php _e('Avg Score:', 'mobility-trailblazers'); ?></strong>
                                <?php echo esc_html($cat_stats['avg_score']); ?>
                            </span>
                        </div>
                        <?php if ($atts['show_chart'] === 'yes'): ?>
                            <div class="mt-category-progress">
                                <div class="mt-progress-bar">
                                    <div class="mt-progress-fill" style="width: <?php echo esc_attr($cat_stats['completion_rate']); ?>%"></div>
                                </div>
                                <span class="mt-progress-label"><?php echo esc_html($cat_stats['completion_rate']); ?>% <?php _e('Complete', 'mobility-trailblazers'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
    <?php elseif ($atts['type'] === 'criteria'): ?>
        <div class="mt-stats-criteria">
            <h3><?php _e('Average Scores by Criteria', 'mobility-trailblazers'); ?></h3>
            
            <div class="mt-criteria-stats">
                <?php
                $criteria = array(
                    'courage' => __('Courage & Pioneer Spirit', 'mobility-trailblazers'),
                    'innovation' => __('Degree of Innovation', 'mobility-trailblazers'),
                    'implementation' => __('Implementation & Impact', 'mobility-trailblazers'),
                    'relevance' => __('Mobility Transformation Relevance', 'mobility-trailblazers'),
                    'visibility' => __('Role Model & Visibility', 'mobility-trailblazers'),
                );
                
                foreach ($criteria as $key => $label):
                    $score = isset($stats['by_criteria'][$key]) ? $stats['by_criteria'][$key] : 0;
                    $percentage = ($score / 10) * 100;
                ?>
                    <div class="mt-criteria-row">
                        <div class="mt-criteria-label"><?php echo esc_html($label); ?></div>
                        <div class="mt-criteria-bar">
                            <div class="mt-criteria-fill" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                        </div>
                        <div class="mt-criteria-score"><?php echo number_format($score, 1); ?>/10</div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($atts['show_chart'] === 'yes'): ?>
                <div class="mt-criteria-chart">
                    <canvas id="mt-criteria-radar"></canvas>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($atts['show_chart'] === 'yes'): ?>
<script>
jQuery(document).ready(function($) {
    // Initialize charts based on type
    <?php if ($atts['type'] === 'overview'): ?>
        // Overview chart
        var ctx = document.getElementById('mt-overview-chart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
$dates = isset($stats["daily_evaluations"]) && is_array($stats["daily_evaluations"]) ? isset($stats["daily_evaluations"]) && is_array($stats["daily_evaluations"]) ? array_keys($stats["daily_evaluations"]) : array() : array();
                    datasets: [{
                        label: '<?php _e('Evaluations', 'mobility-trailblazers'); ?>',
                        data: <?php echo json_encode(array_values($stats['daily_evaluations'])); ?>,
                        backgroundColor: '#0073aa'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    <?php elseif ($atts['type'] === 'criteria'): ?>
        // Criteria radar chart
        var ctx = document.getElementById('mt-criteria-radar');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'radar',
                data: {
                    labels: <?php echo json_encode(array_values($criteria)); ?>,
                    datasets: [{
                        label: '<?php _e('Average Score', 'mobility-trailblazers'); ?>',
                        data: <?php echo json_encode(array_values($stats['by_criteria'])); ?>,
                        backgroundColor: 'rgba(0, 115, 170, 0.2)',
                        borderColor: '#0073aa',
                        pointBackgroundColor: '#0073aa'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scale: {
                        ticks: {
                            beginAtZero: true,
                            max: 10
                        }
                    }
                }
            });
        }
    <?php endif; ?>
});
</script>
<?php endif; ?> 