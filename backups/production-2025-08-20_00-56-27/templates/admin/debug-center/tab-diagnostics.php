<?php
/**
 * Debug Center - Diagnostics Tab
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Run diagnostics if requested
$diagnostics = null;
$run_type = 'full';

if (isset($_POST['run_diagnostic']) && wp_verify_nonce($_POST['_wpnonce'], 'mt_run_diagnostic')) {
    $run_type = sanitize_text_field($_POST['diagnostic_type'] ?? 'full');
    $diagnostics = $diagnostic_service->run_diagnostic($run_type);
}
?>

<div class="mt-diagnostics-tab">
    <h2><?php _e('System Diagnostics', 'mobility-trailblazers'); ?></h2>
    
    <div class="diagnostic-controls">
        <form method="post" class="diagnostic-form">
            <?php wp_nonce_field('mt_run_diagnostic'); ?>
            
            <label for="diagnostic_type"><?php _e('Diagnostic Type:', 'mobility-trailblazers'); ?></label>
            <select name="diagnostic_type" id="diagnostic_type">
                <option value="full"><?php _e('Full System Check', 'mobility-trailblazers'); ?></option>
                <option value="environment"><?php _e('Environment Only', 'mobility-trailblazers'); ?></option>
                <option value="wordpress"><?php _e('WordPress Health', 'mobility-trailblazers'); ?></option>
                <option value="database"><?php _e('Database Health', 'mobility-trailblazers'); ?></option>
                <option value="plugin"><?php _e('Plugin Components', 'mobility-trailblazers'); ?></option>
                <option value="filesystem"><?php _e('Filesystem', 'mobility-trailblazers'); ?></option>
                <option value="performance"><?php _e('Performance', 'mobility-trailblazers'); ?></option>
                <option value="security"><?php _e('Security', 'mobility-trailblazers'); ?></option>
                <option value="errors"><?php _e('Error Logs', 'mobility-trailblazers'); ?></option>
            </select>
            
            <button type="submit" name="run_diagnostic" class="button button-primary">
                <span class="dashicons dashicons-search"></span>
                <?php _e('Run Diagnostic', 'mobility-trailblazers'); ?>
            </button>
            
            <?php if ($diagnostics): ?>
            <button type="button" class="button button-secondary" onclick="exportDiagnostics()">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Results', 'mobility-trailblazers'); ?>
            </button>
            <?php endif; ?>
        </form>
    </div>
    
    <?php if ($diagnostics): ?>
    <div class="diagnostic-results">
        <h3>
            <?php _e('Diagnostic Results', 'mobility-trailblazers'); ?>
            <span class="execution-time">
                <?php 
                printf(
                    __('Completed in %s seconds', 'mobility-trailblazers'),
                    number_format($diagnostics['execution_time'] ?? 0, 3)
                );
                ?>
            </span>
        </h3>
        
        <?php if (isset($diagnostics['overall_status'])): ?>
        <div class="overall-status status-<?php echo esc_attr($diagnostics['overall_status']); ?>">
            <strong><?php _e('Overall Status:', 'mobility-trailblazers'); ?></strong>
            <?php
            $status_labels = [
                'healthy' => __('✓ Healthy', 'mobility-trailblazers'),
                'warning' => __('⚠ Warning', 'mobility-trailblazers'),
                'critical' => __('✗ Critical', 'mobility-trailblazers')
            ];
            echo esc_html($status_labels[$diagnostics['overall_status']] ?? $diagnostics['overall_status']);
            ?>
        </div>
        <?php endif; ?>
        
        <?php
        // Display diagnostic sections
        $sections = [
            'environment' => __('Environment', 'mobility-trailblazers'),
            'wordpress' => __('WordPress', 'mobility-trailblazers'),
            'database' => __('Database', 'mobility-trailblazers'),
            'plugin' => __('Plugin Components', 'mobility-trailblazers'),
            'filesystem' => __('Filesystem', 'mobility-trailblazers'),
            'performance' => __('Performance', 'mobility-trailblazers'),
            'security' => __('Security', 'mobility-trailblazers'),
            'errors' => __('Errors', 'mobility-trailblazers')
        ];
        
        foreach ($sections as $key => $title):
            if (!isset($diagnostics[$key])) continue;
        ?>
        <div class="diagnostic-section">
            <h4><?php echo esc_html($title); ?></h4>
            
            <?php if ($key === 'database' && isset($diagnostics[$key]['tables'])): ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Table', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Rows', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Auto Increment', 'mobility-trailblazers'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diagnostics[$key]['tables'] as $table_name => $table_info): ?>
                        <tr>
                            <td><code><?php echo esc_html($table_name); ?></code></td>
                            <td>
                                <?php if ($table_info['exists']): ?>
                                    <span class="status-indicator status-ok">✓</span>
                                <?php else: ?>
                                    <span class="status-indicator status-error">✗ Missing</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo isset($table_info['row_count']) ? intval($table_info['row_count']) : '-'; ?></td>
                            <td><?php echo isset($table_info['auto_increment']) ? intval($table_info['auto_increment']) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (isset($diagnostics[$key]['orphaned_evaluations']) && $diagnostics[$key]['orphaned_evaluations'] > 0): ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php 
                        printf(
                            __('Found %d orphaned evaluations that should be cleaned up.', 'mobility-trailblazers'),
                            $diagnostics[$key]['orphaned_evaluations']
                        );
                        ?>
                    </p>
                </div>
                <?php endif; ?>
                
            <?php elseif ($key === 'security' && isset($diagnostics[$key]['recommendations'])): ?>
                <?php if (!empty($diagnostics[$key]['recommendations'])): ?>
                <div class="security-recommendations">
                    <h5><?php _e('Security Recommendations:', 'mobility-trailblazers'); ?></h5>
                    <ul>
                        <?php foreach ($diagnostics[$key]['recommendations'] as $recommendation): ?>
                        <li><?php echo esc_html($recommendation); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
            <?php elseif ($key === 'performance' && isset($diagnostics[$key]['memory'])): ?>
                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <th><?php _e('Memory Usage', 'mobility-trailblazers'); ?></th>
                            <td>
                                <?php 
                                $memory = $diagnostics[$key]['memory'];
                                printf(
                                    __('%s / %s (%s%%)', 'mobility-trailblazers'),
                                    size_format($memory['current']),
                                    size_format($memory['limit']),
                                    $memory['usage_percentage']
                                );
                                ?>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $memory['usage_percentage']; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Peak Memory', 'mobility-trailblazers'); ?></th>
                            <td><?php echo size_format($memory['peak']); ?></td>
                        </tr>
                        <?php if (isset($diagnostics[$key]['database'])): ?>
                        <tr>
                            <th><?php _e('Database Queries', 'mobility-trailblazers'); ?></th>
                            <td><?php echo intval($diagnostics[$key]['database']['queries']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
            <?php else: ?>
                <pre class="diagnostic-data"><?php 
                    echo esc_html(json_encode($diagnostics[$key], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); 
                ?></pre>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <!-- Export Data (hidden) -->
        <textarea id="diagnostic-export-data" style="display:none;"><?php 
            echo esc_textarea(json_encode($diagnostics, JSON_PRETTY_PRINT));
        ?></textarea>
    </div>
    <?php else: ?>
    <div class="no-diagnostics">
        <p><?php _e('Click "Run Diagnostic" to perform a system health check.', 'mobility-trailblazers'); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.diagnostic-controls {
    background: #f1f1f1;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.diagnostic-form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.diagnostic-results {
    margin-top: 20px;
}

.execution-time {
    float: right;
    font-weight: normal;
    font-size: 13px;
    color: #666;
}

.overall-status {
    padding: 10px;
    margin: 15px 0;
    border-radius: 4px;
}

.overall-status.status-healthy {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.overall-status.status-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

.overall-status.status-critical {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.diagnostic-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.diagnostic-section h4 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.status-indicator {
    font-weight: bold;
}

.status-indicator.status-ok {
    color: #46b450;
}

.status-indicator.status-error {
    color: #dc3232;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 5px;
}

.progress-fill {
    height: 100%;
    background: #0073aa;
    transition: width 0.3s ease;
}

.diagnostic-data {
    background: #f6f7f7;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow-x: auto;
    max-height: 400px;
}

.security-recommendations {
    background: #fff9e6;
    padding: 15px;
    border-left: 4px solid #ffb900;
    margin-top: 10px;
}

.security-recommendations ul {
    margin: 10px 0 0 20px;
}
</style>

<script>
function exportDiagnostics() {
    const data = document.getElementById('diagnostic-export-data').value;
    const blob = new Blob([data], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'mt-diagnostics-' + new Date().toISOString().slice(0,10) + '.json';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>