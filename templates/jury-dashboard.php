<?php
/**
 * Jury Dashboard Shortcode Template
 * File: templates/shortcodes/jury-dashboard.php
 * 
 * Available variables:
 * - $atts: Shortcode attributes
 * - $assignments: Array of assigned candidates
 * - $total_assigned: Total number of assigned candidates
 * - $evaluated_count: Number of evaluated candidates
 * - $pending_count: Number of pending evaluations
 * - $completion_percentage: Completion percentage
 * - $current_user_id: Current user ID
 * - $jury_member_id: Jury member post ID
 */

if (!defined('ABSPATH')) {
    exit;
}

use MobilityTrailblazers\Core\Evaluation;
?>

<div class="mt-jury-dashboard">
    <?php if ($atts['show_stats'] === 'true'): ?>
    <div class="mt-dashboard-stats">
        <div class="mt-stat-card">
            <div class="mt-stat-icon">
                <span class="dashicons dashicons-portfolio"></span>
            </div>
            <h3><?php _e('Assigned', 'mobility-trailblazers'); ?></h3>
            <div class="mt-stat-number"><?php echo $total_assigned; ?></div>
            <p><?php _e('Total candidates', 'mobility-trailblazers'); ?></p>
        </div>
        
        <div class="mt-stat-card">
            <div class="mt-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <h3><?php _e('Evaluated', 'mobility-trailblazers'); ?></h3>
            <div class="mt-stat-number"><?php echo $evaluated_count; ?></div>
            <p><?php _e('Completed evaluations', 'mobility-trailblazers'); ?></p>
        </div>
        
        <div class="mt-stat-card">
            <div class="mt-stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <h3><?php _e('Pending', 'mobility-trailblazers'); ?></h3>
            <div class="mt-stat-number"><?php echo $pending_count; ?></div>
            <p><?php _e('Awaiting evaluation', 'mobility-trailblazers'); ?></p>
        </div>
        
        <?php if ($atts['show_progress'] === 'true'): ?>
        <div class="mt-stat-card">
            <div class="mt-stat-icon">
                <span class="dashicons dashicons-chart-pie"></span>
            </div>
            <h3><?php _e('Progress', 'mobility-trailblazers'); ?></h3>
            <div class="mt-stat-number"><?php echo $completion_percentage; ?>%</div>
            <div class="mt-progress-bar">
                <div class="mt-progress-fill" style="width: <?php echo $completion_percentage; ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($atts['show_assignments'] === 'true' && !empty($assignments)): ?>
    <div class="mt-assignments-section">
        <h2><?php _e('Your Assigned Candidates', 'mobility-trailblazers'); ?></h2>
        
        <div class="mt-filters">
            <button class="mt-filter-btn active" data-filter="all">
                <?php _e('All', 'mobility-trailblazers'); ?> (<?php echo $total_assigned; ?>)
            </button>
            <button class="mt-filter-btn" data-filter="evaluated">
                <?php _e('Evaluated', 'mobility-trailblazers'); ?> (<?php echo $evaluated_count; ?>)
            </button>
            <button class="mt-filter-btn" data-filter="pending">
                <?php _e('Pending', 'mobility-trailblazers'); ?> (<?php echo $pending_count; ?>)
            </button>
        </div>
        
        <div class="mt-candidate-list">
            <?php foreach ($assignments as $candidate): 
                $evaluation = new Evaluation();
                $evaluated = $evaluation->has_evaluated($current_user_id, $candidate->ID);
                $company = get_post_meta($candidate->ID, '_mt_company', true);
                $position = get_post_meta($candidate->ID, '_mt_position', true);
                $categories = wp_get_post_terms($candidate->ID, 'mt_category');
                $category_name = !empty($categories) ? $categories[0]->name : '';
            ?>
            <div class="mt-candidate-item <?php echo $evaluated ? 'evaluated' : 'pending'; ?>" data-status="<?php echo $evaluated ? 'evaluated' : 'pending'; ?>">
                <div class="mt-candidate-header">
                    <h3><?php echo esc_html($candidate->post_title); ?></h3>
                    <span class="mt-status-badge <?php echo $evaluated ? 'evaluated' : 'pending'; ?>">
                        <?php echo $evaluated ? __('Evaluated', 'mobility-trailblazers') : __('Pending', 'mobility-trailblazers'); ?>
                    </span>
                </div>
                
                <div class="mt-candidate-meta">
                    <?php if ($position): ?>
                    <p class="mt-position">
                        <span class="dashicons dashicons-businessman"></span>
                        <?php echo esc_html($position); ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($company): ?>
                    <p class="mt-company">
                        <span class="dashicons dashicons-building"></span>
                        <?php echo esc_html($company); ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($category_name): ?>
                    <p class="mt-category">
                        <span class="dashicons dashicons-category"></span>
                        <?php echo esc_html($category_name); ?>
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="mt-candidate-actions">
                    <a href="<?php echo get_permalink($candidate->ID); ?>" class="button button-secondary" target="_blank">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('View Details', 'mobility-trailblazers'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=mt-evaluate&candidate=' . $candidate->ID); ?>" class="button button-primary">
                        <span class="dashicons dashicons-<?php echo $evaluated ? 'edit' : 'star-empty'; ?>"></span>
                        <?php echo $evaluated ? __('Edit Evaluation', 'mobility-trailblazers') : __('Evaluate Now', 'mobility-trailblazers'); ?>
                    </a>
                </div>
                
                <?php if ($evaluated): 
                    $scores = $evaluation->get_evaluation($current_user_id, $candidate->ID);
                    if ($scores):
                ?>
                <div class="mt-evaluation-summary">
                    <h4><?php _e('Your Evaluation', 'mobility-trailblazers'); ?></h4>
                    <div class="mt-score-display">
                        <span class="mt-total-score"><?php echo $scores->total_score; ?>/50</span>
                        <span class="mt-evaluation-date">
                            <?php echo sprintf(
                                __('Evaluated on %s', 'mobility-trailblazers'),
                                date_i18n(get_option('date_format'), strtotime($scores->evaluated_at))
                            ); ?>
                        </span>
                    </div>
                </div>
                <?php endif; endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif ($atts['show_assignments'] === 'true'): ?>
    <div class="mt-no-assignments">
        <p><?php _e('No candidates have been assigned to you yet.', 'mobility-trailblazers'); ?></p>
    </div>
    <?php endif; ?>
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