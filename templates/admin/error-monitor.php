<?php
/**
 * Error Monitor Admin Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.11
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap mt-error-monitor">
    <h1><?php _e('Error Monitor', 'mobility-trailblazers'); ?></h1>
    
    <div class="mt-error-stats">
        <div class="mt-stats-grid">
            <div class="mt-stat-card">
                <h3><?php _e('Total Errors', 'mobility-trailblazers'); ?></h3>
                <div class="mt-stat-number"><?php echo esc_html($stats['total_errors']); ?></div>
            </div>
            
            <div class="mt-stat-card">
                <h3><?php _e('Errors Today', 'mobility-trailblazers'); ?></h3>
                <div class="mt-stat-number"><?php echo esc_html($stats['errors_today']); ?></div>
            </div>
            
            <div class="mt-stat-card">
                <h3><?php _e('This Week', 'mobility-trailblazers'); ?></h3>
                <div class="mt-stat-number"><?php echo esc_html($stats['errors_this_week']); ?></div>
            </div>
            
            <div class="mt-stat-card critical">
                <h3><?php _e('Critical Errors', 'mobility-trailblazers'); ?></h3>
                <div class="mt-stat-number"><?php echo esc_html($stats['critical_errors']); ?></div>
            </div>
        </div>
    </div>
    
    <div class="mt-error-actions">
        <button type="button" class="button button-primary" id="mt-refresh-stats">
            <?php _e('Refresh Stats', 'mobility-trailblazers'); ?>
        </button>
        
        <button type="button" class="button" id="mt-export-logs">
            <?php _e('Export Logs', 'mobility-trailblazers'); ?>
        </button>
        
        <button type="button" class="button button-secondary" id="mt-clear-logs">
            <?php _e('Clear All Logs', 'mobility-trailblazers'); ?>
        </button>
    </div>
    
    <?php if (!empty($error_counts)): ?>
    <div class="mt-error-breakdown">
        <h2><?php _e('Error Breakdown by Level', 'mobility-trailblazers'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Level', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Count', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Percentage', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = array_sum($error_counts);
                foreach ($error_counts as $level => $count): 
                    $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                    $level_class = strtolower($level);
                ?>
                <tr>
                    <td><span class="mt-error-level mt-level-<?php echo esc_attr($level_class); ?>"><?php echo esc_html($level); ?></span></td>
                    <td><?php echo esc_html($count); ?></td>
                    <td><?php echo esc_html($percentage); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="mt-recent-errors">
        <h2><?php _e('Recent Errors', 'mobility-trailblazers'); ?></h2>
        
        <?php if (empty($recent_errors)): ?>
            <div class="notice notice-success">
                <p><?php _e('No errors found. System is running smoothly!', 'mobility-trailblazers'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Level', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Message', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('User', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Context', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_errors as $error): 
                        $level_class = strtolower($error->level);
                        $user = $error->user_id ? get_user_by('id', $error->user_id) : null;
                        $context = json_decode($error->context, true);
                    ?>
                    <tr>
                        <td>
                            <abbr title="<?php echo esc_attr($error->created_at); ?>">
                                <?php echo esc_html(human_time_diff(strtotime($error->created_at), current_time('timestamp'))); ?> ago
                            </abbr>
                        </td>
                        <td>
                            <span class="mt-error-level mt-level-<?php echo esc_attr($level_class); ?>">
                                <?php echo esc_html($error->level); ?>
                            </span>
                        </td>
                        <td>
                            <div class="mt-error-message">
                                <?php echo esc_html($error->message); ?>
                            </div>
                            <?php if ($error->request_uri): ?>
                                <div class="mt-error-uri">
                                    <small><?php echo esc_html($error->request_uri); ?></small>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user): ?>
                                <?php echo esc_html($user->display_name); ?>
                                <br><small><?php echo esc_html($user->user_login); ?></small>
                            <?php elseif ($error->user_id): ?>
                                User ID: <?php echo esc_html($error->user_id); ?>
                            <?php else: ?>
                                <em><?php _e('Guest', 'mobility-trailblazers'); ?></em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($context && is_array($context)): ?>
                                <details>
                                    <summary><?php _e('View Context', 'mobility-trailblazers'); ?></summary>
                                    <pre class="mt-error-context"><?php echo esc_html(wp_json_encode($context, JSON_PRETTY_PRINT)); ?></pre>
                                </details>
                            <?php else: ?>
                                <em><?php _e('No context', 'mobility-trailblazers'); ?></em>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.mt-error-monitor {
    max-width: 1200px;
}

.mt-stats-grid {
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
    text-align: center;
}

.mt-stat-card.critical {
    border-color: #dc3232;
    background: #fef7f7;
}

.mt-stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
}

.mt-stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #23282d;
}

.mt-stat-card.critical .mt-stat-number {
    color: #dc3232;
}

.mt-error-actions {
    margin: 20px 0;
}

.mt-error-actions .button {
    margin-right: 10px;
}

.mt-error-level {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.mt-level-critical {
    background: #dc3232;
    color: white;
}

.mt-level-error {
    background: #f56565;
    color: white;
}

.mt-level-warning {
    background: #ed8936;
    color: white;
}

.mt-level-info {
    background: #4299e1;
    color: white;
}

.mt-level-debug {
    background: #9f7aea;
    color: white;
}

.mt-error-message {
    font-weight: 500;
}

.mt-error-uri {
    color: #666;
    font-family: monospace;
}

.mt-error-context {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 3px;
    padding: 10px;
    font-size: 12px;
    max-height: 200px;
    overflow-y: auto;
    white-space: pre-wrap;
}

.mt-error-breakdown {
    margin: 30px 0;
}

.mt-recent-errors {
    margin: 30px 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#mt-refresh-stats').on('click', function() {
        location.reload();
    });
    
    $('#mt-export-logs').on('click', function() {
        var form = $('<form method="post" action="' + ajaxurl + '">' +
            '<input type="hidden" name="action" value="mt_export_error_logs">' +
            '<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mt_admin_nonce'); ?>">' +
            '</form>');
        $('body').append(form);
        form.submit();
        form.remove();
    });
    
    $('#mt-clear-logs').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to clear all error logs? This action cannot be undone.', 'mobility-trailblazers'); ?>')) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'mt_clear_error_logs',
            nonce: '<?php echo wp_create_nonce('mt_admin_nonce'); ?>'
        })
        .done(function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data || 'Failed to clear logs');
            }
        })
        .fail(function() {
            alert('Network error occurred');
        });
    });
});
</script>
