<?php
/**
 * Evaluation Statistics Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Template variables
$type = $atts['type'];
$show_chart = $atts['show_chart'] === 'yes';
?>

<div class="mt-root">
<div class="mt-evaluation-stats">
    <?php if ($type === 'summary') : ?>
        <div class="mt-stats-summary">
            <h3><?php _e('Evaluation Overview', 'mobility-trailblazers'); ?></h3>
            
            <div class="mt-stats-grid">
                <div class="mt-stat-box">
                    <div class="mt-stat-number"><?php echo number_format($stats['total'] ?? 0); ?></div>
                    <div class="mt-stat-label"><?php _e('Total Evaluations', 'mobility-trailblazers'); ?></div>
                </div>
                
                <div class="mt-stat-box">
                    <div class="mt-stat-number"><?php echo number_format($stats['completed'] ?? 0); ?></div>
                    <div class="mt-stat-label"><?php _e('Completed', 'mobility-trailblazers'); ?></div>
                </div>
                
                <div class="mt-stat-box">
                    <div class="mt-stat-number"><?php echo number_format($stats['drafts'] ?? 0); ?></div>
                    <div class="mt-stat-label"><?php _e('In Progress', 'mobility-trailblazers'); ?></div>
                </div>
                
                <div class="mt-stat-box">
                    <div class="mt-stat-number"><?php echo number_format($stats['average_score'] ?? 0, 1); ?></div>
                    <div class="mt-stat-label"><?php _e('Average Score', 'mobility-trailblazers'); ?></div>
                </div>
            </div>
            
            <?php if (!empty($stats['by_criteria'])) : ?>
                <div class="mt-criteria-stats">
                    <h4><?php _e('Average Scores by Criteria', 'mobility-trailblazers'); ?></h4>
                    
                    <div class="mt-criteria-bars">
                        <?php foreach ($stats['by_criteria'] as $criterion => $avg) : 
                            $percentage = ($avg / 10) * 100;
                            $label = ucwords(str_replace('_', ' ', $criterion));
                        ?>
                            <div class="mt-criterion-bar">
                                <div class="mt-bar-label"><?php echo esc_html($label); ?></div>
                                <div class="mt-bar-container">
                                    <div class="mt-bar-fill" style="width: <?php echo esc_attr($percentage); ?>%">
                                        <span class="mt-bar-value"><?php echo number_format($avg, 1); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    <?php elseif ($type === 'by-category') : ?>
        <div class="mt-stats-by-category">
            <h3><?php _e('Evaluations by Category', 'mobility-trailblazers'); ?></h3>
            
            <?php if (!empty($stats['by_category'] ?? [])) : ?>
                <div class="mt-category-stats">
                    <?php foreach ($stats['by_category'] as $category_data) : ?>
                        <div class="mt-category-stat-item">
                            <h4><?php echo esc_html($category_data['name'] ?? ''); ?></h4>
                            <div class="mt-category-metrics">
                                <span class="mt-metric">
                                    <strong><?php echo number_format($category_data['total'] ?? 0); ?></strong>
                                    <?php _e('Evaluations', 'mobility-trailblazers'); ?>
                                </span>
                                <span class="mt-metric">
                                    <strong><?php echo number_format($category_data['avg_score'] ?? 0, 1); ?></strong>
                                    <?php _e('Avg Score', 'mobility-trailblazers'); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p><?php _e('No category data available.', 'mobility-trailblazers'); ?></p>
            <?php endif; ?>
        </div>
        
    <?php elseif ($type === 'by-jury') : ?>
        <div class="mt-stats-by-jury">
            <h3><?php _e('Jury Member Activity', 'mobility-trailblazers'); ?></h3>
            
            <?php if (!empty($stats['by_jury_member'] ?? [])) : ?>
                <table class="mt-jury-stats-table">
                    <thead>
                        <tr>
                            <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Assigned', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Completed', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Progress', 'mobility-trailblazers'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_jury_member'] as $jury_data) : 
                            $progress_percentage = ($jury_data['assigned'] ?? 0) > 0 
                                ? (($jury_data['completed'] ?? 0) / ($jury_data['assigned'] ?? 1)) * 100 
                                : 0;
                        ?>
                            <tr>
                                <td><?php echo esc_html($jury_data['name'] ?? ''); ?></td>
                                <td><?php echo number_format($jury_data['assigned'] ?? 0); ?></td>
                                <td><?php echo number_format($jury_data['completed'] ?? 0); ?></td>
                                <td>
                                    <div class="mt-progress-mini">
                                        <div class="mt-progress-mini-fill" 
                                             style="width: <?php echo esc_attr($progress_percentage); ?>%"></div>
                                        <span class="mt-progress-mini-text">
                                            <?php echo number_format($progress_percentage, 0); ?>%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No jury member data available.', 'mobility-trailblazers'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</div><!-- .mt-root --> 