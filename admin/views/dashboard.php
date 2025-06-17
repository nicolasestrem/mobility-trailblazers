<?php
/**
 * Dashboard Admin View
 *
 * @package MobilityTrailblazers
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
global $wpdb;
$stats = array(
    'total_candidates' => wp_count_posts('mt_candidate')->publish,
    'total_jury' => wp_count_posts('mt_jury')->publish,
    'total_votes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE is_active = 1"),
    'total_evaluations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores"),
    'avg_score' => $wpdb->get_var("SELECT AVG(total_score) FROM {$wpdb->prefix}mt_votes WHERE is_active = 1"),
    'completion_rate' => 0
);

// Calculate completion rate
if ($stats['total_candidates'] > 0 && $stats['total_jury'] > 0) {
    $expected_evaluations = $stats['total_candidates'] * $stats['total_jury'];
    $stats['completion_rate'] = round(($stats['total_evaluations'] / $expected_evaluations) * 100, 1);
}

// Get recent activity
$recent_evaluations = $wpdb->get_results("
    SELECT v.*, c.post_title as candidate_name, j.post_title as jury_name
    FROM {$wpdb->prefix}mt_votes v
    LEFT JOIN {$wpdb->posts} c ON v.candidate_id = c.ID
    LEFT JOIN {$wpdb->posts} j ON v.jury_member_id = j.ID
    WHERE v.is_active = 1
    ORDER BY v.created_at DESC
    LIMIT 5
");

// Get top candidates
$top_candidates = $wpdb->get_results("
    SELECT 
        c.ID,
        c.post_title as name,
        AVG(v.total_score) as avg_score,
        COUNT(v.id) as vote_count
    FROM {$wpdb->posts} c
    LEFT JOIN {$wpdb->prefix}mt_votes v ON c.ID = v.candidate_id AND v.is_active = 1
    WHERE c.post_type = 'mt_candidate' AND c.post_status = 'publish'
    GROUP BY c.ID
    HAVING vote_count > 0
    ORDER BY avg_score DESC
    LIMIT 10
");

// Get jury progress
$jury_progress = $wpdb->get_results("
    SELECT 
        j.ID,
        j.post_title as name,
        COUNT(DISTINCT v.candidate_id) as evaluated,
        (SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_mt_assigned_jury_member' AND meta_value = j.ID) as assigned
    FROM {$wpdb->posts} j
    LEFT JOIN {$wpdb->prefix}mt_votes v ON j.ID = v.jury_member_id AND v.is_active = 1
    WHERE j.post_type = 'mt_jury' AND j.post_status = 'publish'
    GROUP BY j.ID
    ORDER BY j.post_title
");
?>

<div class="wrap">
    <h1><?php _e('Mobility Trailblazers Dashboard', 'mobility-trailblazers'); ?></h1>
    
    <!-- Quick Stats -->
    <div class="mt-dashboard-stats">
        <div class="mt-stat-card">
            <div class="mt-stat-icon candidates"></div>
            <div class="mt-stat-content">
                <div class="mt-stat-number"><?php echo number_format($stats['total_candidates']); ?></div>
                <div class="mt-stat-label"><?php _e('Total Candidates', 'mobility-trailblazers'); ?></div>
            </div>
        </div>
        
        <div class="mt-stat-card">
            <div class="mt-stat-icon jury"></div>
            <div class="mt-stat-content">
                <div class="mt-stat-number"><?php echo number_format($stats['total_jury']); ?></div>
                <div class="mt-stat-label"><?php _e('Jury Members', 'mobility-trailblazers'); ?></div>
            </div>
        </div>
        
        <div class="mt-stat-card">
            <div class="mt-stat-icon evaluations"></div>
            <div class="mt-stat-content">
                <div class="mt-stat-number"><?php echo number_format($stats['total_evaluations']); ?></div>
                <div class="mt-stat-label"><?php _e('Total Evaluations', 'mobility-trailblazers'); ?></div>
            </div>
        </div>
        
        <div class="mt-stat-card">
            <div class="mt-stat-icon score"></div>
            <div class="mt-stat-content">
                <div class="mt-stat-number"><?php echo number_format($stats['avg_score'], 1); ?>/50</div>
                <div class="mt-stat-label"><?php _e('Average Score', 'mobility-trailblazers'); ?></div>
            </div>
        </div>
        
        <div class="mt-stat-card">
            <div class="mt-stat-icon completion"></div>
            <div class="mt-stat-content">
                <div class="mt-stat-number"><?php echo $stats['completion_rate']; ?>%</div>
                <div class="mt-stat-label"><?php _e('Completion Rate', 'mobility-trailblazers'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Main Content Grid -->
    <div class="mt-dashboard-grid">
        <!-- Recent Activity -->
        <div class="mt-dashboard-section">
            <h2><?php _e('Recent Evaluations', 'mobility-trailblazers'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Score', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_evaluations)): ?>
                        <tr>
                            <td colspan="4"><?php _e('No recent evaluations', 'mobility-trailblazers'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_evaluations as $eval): ?>
                            <tr>
                                <td><?php echo human_time_diff(strtotime($eval->created_at), current_time('timestamp')) . ' ' . __('ago', 'mobility-trailblazers'); ?></td>
                                <td><?php echo esc_html($eval->jury_name); ?></td>
                                <td><?php echo esc_html($eval->candidate_name); ?></td>
                                <td><strong><?php echo esc_html($eval->total_score); ?>/50</strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Top Candidates -->
        <div class="mt-dashboard-section">
            <h2><?php _e('Top Candidates', 'mobility-trailblazers'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Rank', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Avg Score', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Evaluations', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_candidates)): ?>
                        <tr>
                            <td colspan="4"><?php _e('No evaluations yet', 'mobility-trailblazers'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php $rank = 1; ?>
                        <?php foreach ($top_candidates as $candidate): ?>
                            <tr>
                                <td><?php echo $rank++; ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($candidate->ID); ?>">
                                        <?php echo esc_html($candidate->name); ?>
                                    </a>
                                </td>
                                <td><strong><?php echo number_format($candidate->avg_score, 1); ?>/50</strong></td>
                                <td><?php echo intval($candidate->vote_count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Jury Progress -->
    <div class="mt-dashboard-section full-width">
        <h2><?php _e('Jury Member Progress', 'mobility-trailblazers'); ?></h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Assigned', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Evaluated', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Progress', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jury_progress)): ?>
                    <tr>
                        <td colspan="5"><?php _e('No jury members found', 'mobility-trailblazers'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($jury_progress as $jury): ?>
                        <?php 
                        $progress = $jury->assigned > 0 ? round(($jury->evaluated / $jury->assigned) * 100) : 0;
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($jury->ID); ?>">
                                    <?php echo esc_html($jury->name); ?>
                                </a>
                            </td>
                            <td><?php echo intval($jury->assigned); ?></td>
                            <td><?php echo intval($jury->evaluated); ?></td>
                            <td>
                                <div class="mt-progress-bar">
                                    <div class="mt-progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                    <span class="mt-progress-text"><?php echo $progress; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <button class="button button-small send-reminder" data-jury-id="<?php echo $jury->ID; ?>">
                                    <?php _e('Send Reminder', 'mobility-trailblazers'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Quick Actions -->
    <div class="mt-dashboard-actions">
        <h2><?php _e('Quick Actions', 'mobility-trailblazers'); ?></h2>
        <div class="mt-action-buttons">
            <a href="<?php echo admin_url('post-new.php?post_type=mt_candidate'); ?>" class="button button-primary">
                <?php _e('Add New Candidate', 'mobility-trailblazers'); ?>
            </a>
            <a href="<?php echo admin_url('post-new.php?post_type=mt_jury'); ?>" class="button">
                <?php _e('Add Jury Member', 'mobility-trailblazers'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=mt-assignment-management'); ?>" class="button">
                <?php _e('Manage Assignments', 'mobility-trailblazers'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=mt-voting-results'); ?>" class="button">
                <?php _e('View Results', 'mobility-trailblazers'); ?>
            </a>
            <button class="button" id="export-all-data">
                <?php _e('Export All Data', 'mobility-trailblazers'); ?>
            </button>
        </div>
    </div>
</div>

<style>
/* Dashboard Stats */
.mt-dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.mt-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-stat-icon {
    width: 50px;
    height: 50px;
    background: #f0f0f1;
    border-radius: 50%;
    flex-shrink: 0;
}

.mt-stat-number {
    font-size: 32px;
    font-weight: 600;
    color: #2271b1;
    line-height: 1;
}

.mt-stat-label {
    font-size: 14px;
    color: #646970;
    margin-top: 5px;
}

/* Dashboard Grid */
.mt-dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.mt-dashboard-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-dashboard-section.full-width {
    grid-column: 1 / -1;
}

.mt-dashboard-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

/* Progress Bar */
.mt-progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f1;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.mt-progress-fill {
    height: 100%;
    background: #2271b1;
    transition: width 0.3s ease;
}

.mt-progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: 600;
    color: #23282d;
}

/* Quick Actions */
.mt-dashboard-actions {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
}

.mt-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* Responsive */
@media (max-width: 768px) {
    .mt-dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .mt-dashboard-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Send reminder
    $('.send-reminder').on('click', function() {
        var juryId = $(this).data('jury-id');
        var $button = $(this);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mt_send_reminder',
                jury_id: juryId,
                nonce: '<?php echo wp_create_nonce('mt_admin_nonce'); ?>'
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('<?php _e('Sending...', 'mobility-trailblazers'); ?>');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php _e('Send Reminder', 'mobility-trailblazers'); ?>');
            }
        });
    });
    
    // Export all data
    $('#export-all-data').on('click', function() {
        if (confirm('<?php _e('This will export all award data. Continue?', 'mobility-trailblazers'); ?>')) {
            window.location.href = ajaxurl + '?action=mt_export_all_data&nonce=<?php echo wp_create_nonce('mt_export_nonce'); ?>';
        }
    });
});
</script> 