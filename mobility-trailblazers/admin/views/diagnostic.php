<?php
/**
 * Diagnostic Admin View
 *
 * @package MobilityTrailblazers
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get diagnostic instance
$diagnostic = new MT_Diagnostic();
$results = $diagnostic->get_diagnostic_results();
$system_info = $diagnostic->get_system_info();
?>

<div class="wrap">
    <h1><?php _e('System Diagnostic', 'mobility-trailblazers'); ?></h1>
    
    <div class="mt-diagnostic-header">
        <p><?php _e('This page displays comprehensive system health checks and diagnostic information for the Mobility Trailblazers plugin.', 'mobility-trailblazers'); ?></p>
        <button class="button button-primary" id="refresh-diagnostic"><?php _e('Refresh Diagnostic', 'mobility-trailblazers'); ?></button>
        <button class="button" id="export-diagnostic"><?php _e('Export Report', 'mobility-trailblazers'); ?></button>
    </div>

    <!-- System Information -->
    <div class="mt-diagnostic-section">
        <h2><?php _e('System Information', 'mobility-trailblazers'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <th><?php _e('WordPress Version', 'mobility-trailblazers'); ?></th>
                    <td><?php echo isset($system_info['wordpress_version']) ? esc_html($system_info['wordpress_version']) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <th><?php _e('PHP Version', 'mobility-trailblazers'); ?></th>
                    <td><?php echo isset($system_info['php_version']) ? esc_html($system_info['php_version']) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <th><?php _e('MySQL Version', 'mobility-trailblazers'); ?></th>
                    <td><?php echo isset($system_info['mysql_version']) ? esc_html($system_info['mysql_version']) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Plugin Version', 'mobility-trailblazers'); ?></th>
                    <td><?php echo isset($system_info['plugin_version']) ? esc_html($system_info['plugin_version']) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Memory Limit', 'mobility-trailblazers'); ?></th>
                    <td><?php echo isset($system_info['memory_limit']) ? esc_html($system_info['memory_limit']) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Max Execution Time', 'mobility-trailblazers'); ?></th>
                    <td><?php echo isset($system_info['max_execution_time']) ? esc_html($system_info['max_execution_time']) . ' seconds' : 'N/A'; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Active Theme', 'mobility-trailblazers'); ?></th>
                    <td><?php echo isset($system_info['active_theme']) ? esc_html($system_info['active_theme']) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Debug Mode', 'mobility-trailblazers'); ?></th>
                    <td><?php echo isset($system_info['debug_mode']) ? esc_html($system_info['debug_mode']) : 'N/A'; ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Diagnostic Results -->
    <div class="mt-diagnostic-section">
        <h2><?php _e('Diagnostic Results', 'mobility-trailblazers'); ?></h2>
        
        <?php foreach ($results as $category => $checks): ?>
            <div class="mt-diagnostic-category">
                <h3><?php echo esc_html(ucfirst(str_replace('_', ' ', $category))); ?></h3>
                <table class="widefat mt-diagnostic-table">
                    <thead>
                        <tr>
                            <th><?php _e('Check', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Details', 'mobility-trailblazers'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checks as $check): ?>
                            <tr>
                                <td><?php echo esc_html($check['name']); ?></td>
                                <td>
                                    <?php if ($check['status'] === 'success'): ?>
                                        <span class="mt-status-pass">✓ <?php _e('PASS', 'mobility-trailblazers'); ?></span>
                                    <?php elseif ($check['status'] === 'warning'): ?>
                                        <span class="mt-status-warning">⚠ <?php _e('WARNING', 'mobility-trailblazers'); ?></span>
                                    <?php elseif ($check['status'] === 'info'): ?>
                                        <span class="mt-status-info">ℹ <?php _e('INFO', 'mobility-trailblazers'); ?></span>
                                    <?php else: ?>
                                        <span class="mt-status-fail">✗ <?php _e('FAIL', 'mobility-trailblazers'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($check['message']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Database Statistics -->
    <div class="mt-diagnostic-section">
        <h2><?php _e('Database Statistics', 'mobility-trailblazers'); ?></h2>
        <?php
        global $wpdb;
        $stats = array(
            'candidates' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_candidate' AND post_status = 'publish'"),
            'jury_members' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_jury' AND post_status = 'publish'"),
            'votes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE is_active = 1"),
            'evaluations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores"),
            'backups' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_backup'"),
            'reset_logs' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vote_reset_logs")
        );
        ?>
        <table class="widefat">
            <tbody>
                <tr>
                    <th><?php _e('Total Candidates', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['candidates']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Total Jury Members', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['jury_members']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Active Votes', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['votes']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Total Evaluations', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['evaluations']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Backup Records', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['backups']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Reset Log Entries', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['reset_logs']); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Recent Activity -->
    <div class="mt-diagnostic-section">
        <h2><?php _e('Recent Activity', 'mobility-trailblazers'); ?></h2>
        <?php
        $recent_votes = $wpdb->get_results("
            SELECT v.*, c.post_title as candidate_name, j.post_title as jury_name
            FROM {$wpdb->prefix}mt_votes v
            LEFT JOIN {$wpdb->posts} c ON v.candidate_id = c.ID
            LEFT JOIN {$wpdb->posts} j ON v.jury_member_id = j.ID
            WHERE v.is_active = 1
            ORDER BY v.created_at DESC
            LIMIT 10
        ");
        ?>
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
                <?php if (empty($recent_votes)): ?>
                    <tr>
                        <td colspan="4"><?php _e('No recent activity', 'mobility-trailblazers'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_votes as $vote): ?>
                        <tr>
                            <td><?php echo human_time_diff(strtotime($vote->created_at), current_time('timestamp')) . ' ' . __('ago', 'mobility-trailblazers'); ?></td>
                            <td><?php echo esc_html($vote->jury_name); ?></td>
                            <td><?php echo esc_html($vote->candidate_name); ?></td>
                            <td><?php echo esc_html($vote->total_score); ?>/50</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.mt-diagnostic-header {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-diagnostic-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-diagnostic-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.mt-diagnostic-category {
    margin-bottom: 30px;
}

.mt-diagnostic-category h3 {
    margin-bottom: 10px;
}

.mt-diagnostic-table {
    margin-bottom: 20px;
}

.mt-status-pass {
    color: #00a32a;
    font-weight: 600;
}

.mt-status-warning {
    color: #dba617;
    font-weight: 600;
}

.mt-status-fail {
    color: #d63638;
    font-weight: 600;
}

.mt-status-info {
    color: #2271b1;
    font-weight: 600;
}

.widefat th {
    width: 30%;
    font-weight: 600;
}

.widefat td {
    width: 70%;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Refresh diagnostic
    $('#refresh-diagnostic').on('click', function() {
        location.reload();
    });
    
    // Export diagnostic report
    $('#export-diagnostic').on('click', function() {
        var content = $('.wrap').html();
        var blob = new Blob([content], { type: 'text/html' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'mt-diagnostic-report-' + new Date().toISOString().slice(0, 10) + '.html';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
});
</script> 