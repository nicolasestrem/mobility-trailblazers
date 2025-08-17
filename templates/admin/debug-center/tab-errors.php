<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get error monitor instance
$error_monitor = new \MobilityTrailblazers\Admin\MT_Error_Monitor();

// Get error statistics
$error_stats = $error_monitor->get_error_statistics();
$recent_errors = $error_monitor->get_recent_errors(50);
$error_types = $error_monitor->get_error_types();

// Group errors by type
$grouped_errors = [];
foreach ($recent_errors as $error) {
    $type = $error['type'] ?? 'unknown';
    if (!isset($grouped_errors[$type])) {
        $grouped_errors[$type] = [];
    }
    $grouped_errors[$type][] = $error;
}
?>

<div class="mt-debug-errors">
    <div class="mt-debug-header">
        <h2><?php esc_html_e('Error Monitoring', 'mobility-trailblazers'); ?></h2>
        <p class="description">
            <?php esc_html_e('Monitor and analyze application errors, warnings, and notices.', 'mobility-trailblazers'); ?>
        </p>
    </div>

    <!-- Error Statistics -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Error Statistics', 'mobility-trailblazers'); ?></h3>
        <div class="mt-stats-grid">
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html($error_stats['total_errors']); ?></div>
                <div class="mt-stat-label"><?php esc_html_e('Total Errors', 'mobility-trailblazers'); ?></div>
            </div>
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html($error_stats['errors_today']); ?></div>
                <div class="mt-stat-label"><?php esc_html_e('Errors Today', 'mobility-trailblazers'); ?></div>
            </div>
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html(isset($error_stats['unique_errors']) ? $error_stats['unique_errors'] : 0); ?></div>
                <div class="mt-stat-label"><?php esc_html_e('Unique Errors', 'mobility-trailblazers'); ?></div>
            </div>
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html(isset($error_stats['most_common_type']) ? $error_stats['most_common_type'] : 'None'); ?></div>
                <div class="mt-stat-label"><?php esc_html_e('Most Common Type', 'mobility-trailblazers'); ?></div>
            </div>
        </div>
    </div>

    <!-- Error Type Distribution -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Error Type Distribution', 'mobility-trailblazers'); ?></h3>
        <div class="mt-error-types">
            <?php foreach ($error_types as $type => $count): 
                $percentage = ($error_stats['total_errors'] > 0) ? round(($count / $error_stats['total_errors']) * 100, 1) : 0;
                $severity_class = '';
                switch ($type) {
                    case 'fatal':
                    case 'error':
                        $severity_class = 'error';
                        break;
                    case 'warning':
                        $severity_class = 'warning';
                        break;
                    case 'notice':
                    case 'deprecated':
                        $severity_class = 'notice';
                        break;
                    default:
                        $severity_class = 'info';
                }
            ?>
            <div class="mt-error-type-item">
                <div class="mt-error-type-header">
                    <span class="mt-badge mt-badge-<?php echo esc_attr($severity_class); ?>">
                        <?php echo esc_html(ucfirst($type)); ?>
                    </span>
                    <span class="mt-error-count"><?php echo esc_html($count); ?></span>
                </div>
                <div class="mt-error-type-bar">
                    <div class="mt-error-type-progress" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                </div>
                <div class="mt-error-type-percentage"><?php echo esc_html($percentage); ?>%</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Error Log Controls -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Error Log', 'mobility-trailblazers'); ?></h3>
        <div class="mt-error-controls">
            <div class="mt-error-filters">
                <select id="mt-error-type-filter" class="mt-error-filter">
                    <option value=""><?php esc_html_e('All Types', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($error_types as $type => $count): ?>
                    <option value="<?php echo esc_attr($type); ?>">
                        <?php echo esc_html(ucfirst($type) . ' (' . $count . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" 
                       id="mt-error-search" 
                       class="mt-error-filter" 
                       placeholder="<?php esc_attr_e('Search errors...', 'mobility-trailblazers'); ?>">
                
                <button type="button" class="button mt-refresh-errors">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Refresh', 'mobility-trailblazers'); ?>
                </button>
                
                <button type="button" class="button mt-clear-error-log" 
                        data-confirm="<?php esc_attr_e('Are you sure you want to clear all error logs?', 'mobility-trailblazers'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e('Clear All', 'mobility-trailblazers'); ?>
                </button>
            </div>
            
            <div class="mt-error-actions">
                <button type="button" class="button mt-export-errors">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Export CSV', 'mobility-trailblazers'); ?>
                </button>
                
                <button type="button" class="button mt-email-error-report">
                    <span class="dashicons dashicons-email"></span>
                    <?php esc_html_e('Email Report', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Recent Errors -->
    <div class="mt-debug-section">
        <div class="mt-error-log-container">
            <?php if (empty($recent_errors)): ?>
            <div class="mt-no-errors">
                <span class="dashicons dashicons-yes-alt"></span>
                <p><?php esc_html_e('No errors logged. Your application is running smoothly!', 'mobility-trailblazers'); ?></p>
            </div>
            <?php else: ?>
            <div class="mt-error-entries">
                <?php foreach ($grouped_errors as $type => $errors): ?>
                <div class="mt-error-group" data-type="<?php echo esc_attr($type); ?>">
                    <h4 class="mt-error-group-header">
                        <span class="mt-badge mt-badge-<?php echo esc_attr($type); ?>">
                            <?php echo esc_html(ucfirst($type)); ?>
                        </span>
                        <span class="mt-error-group-count"><?php echo esc_html(count($errors)); ?> entries</span>
                    </h4>
                    
                    <?php foreach ($errors as $error): ?>
                    <div class="mt-error-entry" data-error-id="<?php echo esc_attr($error['id']); ?>">
                        <div class="mt-error-header">
                            <div class="mt-error-time">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html(human_time_diff(strtotime($error['timestamp']), current_time('timestamp')) . ' ago'); ?>
                                <span class="mt-error-timestamp">(<?php echo esc_html($error['timestamp']); ?>)</span>
                            </div>
                            <div class="mt-error-location">
                                <span class="dashicons dashicons-media-code"></span>
                                <code><?php echo esc_html($error['file'] . ':' . $error['line']); ?></code>
                            </div>
                        </div>
                        
                        <div class="mt-error-message">
                            <?php echo esc_html($error['message']); ?>
                        </div>
                        
                        <?php if (!empty($error['context'])): ?>
                        <div class="mt-error-context">
                            <button type="button" class="mt-toggle-context">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                <?php esc_html_e('Show Context', 'mobility-trailblazers'); ?>
                            </button>
                            <pre class="mt-error-context-content" style="display: none;">
<?php echo esc_html(print_r($error['context'], true)); ?>
                            </pre>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error['stack_trace'])): ?>
                        <div class="mt-error-stacktrace">
                            <button type="button" class="mt-toggle-stacktrace">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                <?php esc_html_e('Show Stack Trace', 'mobility-trailblazers'); ?>
                            </button>
                            <pre class="mt-error-stacktrace-content" style="display: none;">
<?php echo esc_html($error['stack_trace']); ?>
                            </pre>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-error-actions">
                            <button type="button" class="mt-dismiss-error" data-error-id="<?php echo esc_attr($error['id']); ?>">
                                <?php esc_html_e('Dismiss', 'mobility-trailblazers'); ?>
                            </button>
                            <button type="button" class="mt-copy-error" data-error-id="<?php echo esc_attr($error['id']); ?>">
                                <?php esc_html_e('Copy', 'mobility-trailblazers'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PHP Error Log File -->
    <?php 
    $error_log_file = ini_get('error_log');
    if ($error_log_file && file_exists($error_log_file)):
        $log_size = filesize($error_log_file);
        $log_size_formatted = size_format($log_size);
    ?>
    <div class="mt-debug-section">
        <h3><?php esc_html_e('PHP Error Log File', 'mobility-trailblazers'); ?></h3>
        <div class="mt-error-log-file">
            <div class="mt-log-file-info">
                <p>
                    <strong><?php esc_html_e('Location:', 'mobility-trailblazers'); ?></strong>
                    <code><?php echo esc_html($error_log_file); ?></code>
                </p>
                <p>
                    <strong><?php esc_html_e('Size:', 'mobility-trailblazers'); ?></strong>
                    <?php echo esc_html($log_size_formatted); ?>
                </p>
                <p>
                    <strong><?php esc_html_e('Last Modified:', 'mobility-trailblazers'); ?></strong>
                    <?php echo esc_html(date('Y-m-d H:i:s', filemtime($error_log_file))); ?>
                </p>
            </div>
            <div class="mt-log-file-actions">
                <button type="button" class="button mt-view-raw-log">
                    <?php esc_html_e('View Last 100 Lines', 'mobility-trailblazers'); ?>
                </button>
                <button type="button" class="button mt-download-log">
                    <?php esc_html_e('Download Log', 'mobility-trailblazers'); ?>
                </button>
                <?php if ($log_size > 10485760): // 10MB ?>
                <button type="button" class="button mt-truncate-log" 
                        data-confirm="<?php esc_attr_e('This will clear the PHP error log file. Continue?', 'mobility-trailblazers'); ?>">
                    <?php esc_html_e('Truncate Log', 'mobility-trailblazers'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>