<?php
/**
 * Coaching Dashboard Template
 *
 * @package MobilityTrailblazers
 * @since 2.2.29
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get coaching data from parent context
$jury_stats = $coaching_data['jury_stats'] ?? [];
$total_assigned = $coaching_data['total_assigned'] ?? 0;
$total_completed = $coaching_data['total_completed'] ?? 0;
$total_drafts = $coaching_data['total_drafts'] ?? 0;
$completion_rate = $coaching_data['completion_rate'] ?? 0;
?>

<div class="wrap">
    <h1><?php _e('Jury Coaching Dashboard', 'mobility-trailblazers'); ?></h1>
    
    <div class="mt-coaching-container">
        <!-- Overview Cards -->
        <div class="mt-stats-cards">
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo intval($total_assigned); ?></div>
                <div class="mt-stat-label"><?php _e('Total Assigned', 'mobility-trailblazers'); ?></div>
            </div>
            
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo intval($total_completed); ?></div>
                <div class="mt-stat-label"><?php _e('Completed', 'mobility-trailblazers'); ?></div>
            </div>
            
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo intval($total_drafts); ?></div>
                <div class="mt-stat-label"><?php _e('In Draft', 'mobility-trailblazers'); ?></div>
            </div>
            
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo number_format($completion_rate, 1); ?>%</div>
                <div class="mt-stat-label"><?php _e('Completion Rate', 'mobility-trailblazers'); ?></div>
            </div>
        </div>
        
        <!-- Jury Progress Table -->
        <div class="mt-coaching-stats mt-section">
            <h2><?php _e('Jury Member Progress', 'mobility-trailblazers'); ?></h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                        <th class="text-center"><?php _e('Assigned', 'mobility-trailblazers'); ?></th>
                        <th class="text-center"><?php _e('Completed', 'mobility-trailblazers'); ?></th>
                        <th class="text-center"><?php _e('Drafts', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Progress', 'mobility-trailblazers'); ?></th>
                        <th class="text-center"><?php _e('Avg Score', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Last Activity', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jury_stats)): ?>
                    <tr>
                        <td colspan="9" class="text-center">
                            <?php _e('No jury members found', 'mobility-trailblazers'); ?>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($jury_stats as $jury): ?>
                        <?php 
                            $progress = $jury->assigned > 0 ? round(($jury->completed / $jury->assigned) * 100) : 0;
                            $pending = $jury->assigned - $jury->completed;
                            $progress_class = $progress >= 100 ? 'complete' : ($progress >= 50 ? 'partial' : 'low');
                        ?>
                        <tr class="jury-row <?php echo $progress >= 100 ? 'completed' : ''; ?>">
                            <td>
                                <strong><?php echo esc_html($jury->display_name); ?></strong>
                                <?php if ($pending > 0): ?>
                                <span class="pending-badge"><?php echo sprintf(__('%d pending', 'mobility-trailblazers'), $pending); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo intval($jury->assigned); ?></td>
                            <td class="text-center">
                                <strong><?php echo intval($jury->completed); ?></strong>
                            </td>
                            <td class="text-center">
                                <?php if ($jury->drafts > 0): ?>
                                    <span class="draft-count"><?php echo intval($jury->drafts); ?></span>
                                <?php else: ?>
                                    0
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="mt-progress-bar">
                                    <div class="mt-progress-bar-fill <?php echo $progress_class; ?>" style="width: <?php echo $progress; ?>%">
                                        <span class="progress-text"><?php echo $progress; ?>%</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php echo $jury->avg_score ? number_format($jury->avg_score, 1) : '-'; ?>
                            </td>
                            <td>
                                <?php 
                                if ($jury->last_activity) {
                                    $date = new DateTime($jury->last_activity);
                                    $now = new DateTime();
                                    $interval = $now->diff($date);
                                    
                                    if ($interval->days == 0) {
                                        echo __('Today', 'mobility-trailblazers');
                                    } elseif ($interval->days == 1) {
                                        echo __('Yesterday', 'mobility-trailblazers');
                                    } elseif ($interval->days < 7) {
                                        echo sprintf(__('%d days ago', 'mobility-trailblazers'), $interval->days);
                                    } else {
                                        echo $date->format('M j, Y');
                                    }
                                } else {
                                    echo '<span class="no-activity">' . __('Never', 'mobility-trailblazers') . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <!-- Email functionality removed -->
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Coaching Actions -->
        <div class="mt-coaching-actions mt-section">
            <h2><?php _e('Coaching Actions', 'mobility-trailblazers'); ?></h2>
            
            <div class="mt-button-group">
                <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" style="display: inline;">
                    <input type="hidden" name="action" value="mt_export_coaching_report">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mt_coaching_nonce'); ?>">
                    <button type="submit" class="mt-button-secondary" id="export-coaching-report">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Coaching Report', 'mobility-trailblazers'); ?>
                    </button>
                </form>
                
                <button class="mt-button-secondary" id="refresh-stats">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh Statistics', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="mt-recent-activity mt-section">
            <h2><?php _e('Recent Evaluation Activity', 'mobility-trailblazers'); ?></h2>
            
            <?php
            // Get recent evaluations
            global $wpdb;
            $recent = $wpdb->get_results("
                SELECT 
                    e.*,
                    u.display_name as jury_name,
                    p.post_title as candidate_name
                FROM {$wpdb->prefix}mt_evaluations e
                LEFT JOIN {$wpdb->users} u ON e.jury_member_id = u.ID
                LEFT JOIN {$wpdb->posts} p ON e.candidate_id = p.ID
                ORDER BY e.updated_at DESC
                LIMIT 10
            ");
            ?>
            
            <?php if ($recent): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Score', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $activity): ?>
                    <tr>
                        <td>
                            <?php 
                            $time = new DateTime($activity->updated_at);
                            echo $time->format('M j, g:i A');
                            ?>
                        </td>
                        <td><?php echo esc_html($activity->jury_name); ?></td>
                        <td><?php echo esc_html($activity->candidate_name); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $activity->status; ?>">
                                <?php echo ucfirst($activity->status); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $total = $activity->courage_score + $activity->innovation_score + 
                                    $activity->implementation_score + $activity->relevance_score + $activity->visibility_score;
                            echo $total;
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p><?php _e('No recent activity', 'mobility-trailblazers'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Coaching Dashboard Specific Styles */
.mt-coaching-container {
    max-width: 1400px;
    margin: 20px 0;
}

.mt-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.mt-stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.mt-stat-value {
    font-size: 36px;
    font-weight: bold;
    color: #26a69a;
    margin-bottom: 10px;
}

.mt-stat-label {
    color: #666;
    font-size: 14px;
    text-transform: uppercase;
}

.mt-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.text-center {
    text-align: center;
}

.pending-badge {
    display: inline-block;
    background: #ff9800;
    color: white;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 5px;
}

.draft-count {
    background: #2196F3;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.jury-row.completed {
    background: #f0f9f0 !important;
}

.no-activity {
    color: #999;
    font-style: italic;
}

.status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.status-submitted {
    background: #4caf50;
    color: white;
}

.status-draft {
    background: #2196F3;
    color: white;
}

.mt-progress-bar {
    height: 24px;
    background: #f0f0f0;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.mt-progress-bar-fill {
    height: 100%;
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mt-progress-bar-fill.complete {
    background: #4caf50;
}

.mt-progress-bar-fill.partial {
    background: #ff9800;
}

.mt-progress-bar-fill.low {
    background: #f44336;
}

.progress-text {
    color: white;
    font-size: 12px;
    font-weight: bold;
}
</style>