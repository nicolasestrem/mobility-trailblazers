<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get maintenance tools instance
$maintenance_tools = new \MobilityTrailblazers\Admin\MT_Maintenance_Tools();
$operations = $maintenance_tools->get_operations();
$environment = (new \MobilityTrailblazers\Admin\MT_Debug_Manager())->get_environment();
?>

<div class="mt-debug-tools">
    <div class="mt-debug-header">
        <h2><?php esc_html_e('Maintenance Tools', 'mobility-trailblazers'); ?></h2>
        <p class="description">
            <?php esc_html_e('System maintenance operations for cache management, data cleanup, and system optimization.', 'mobility-trailblazers'); ?>
        </p>
        <?php if ($environment === 'production'): ?>
        <div class="mt-warning-notice">
            <span class="dashicons dashicons-warning"></span>
            <?php esc_html_e('You are in production environment. Some operations may affect live data. Please proceed with caution.', 'mobility-trailblazers'); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cache Management -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Cache Management', 'mobility-trailblazers'); ?></h3>
        <div class="mt-tools-grid">
            <?php if (isset($operations['cache']['operations']) && is_array($operations['cache']['operations'])): ?>
                <?php foreach ($operations['cache']['operations'] as $op_key => $operation): 
                    if (!is_array($operation)) continue;
                ?>
            <div class="mt-tool-card">
                <div class="mt-tool-icon">
                    <span class="dashicons dashicons-<?php echo esc_attr($operation['icon'] ?? 'admin-tools'); ?>"></span>
                </div>
                <h4><?php echo esc_html(isset($operation['title']) ? $operation['title'] : 'Operation'); ?></h4>
                <p><?php echo esc_html(isset($operation['description']) ? $operation['description'] : ''); ?></p>
                
                <?php if (!empty($operation['stats'])): ?>
                <div class="mt-tool-stats">
                    <?php foreach ($operation['stats'] as $stat_key => $stat_value): ?>
                    <div class="mt-stat-item">
                        <span class="mt-stat-label"><?php echo esc_html($stat_key); ?>:</span>
                        <span class="mt-stat-value"><?php echo esc_html($stat_value); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <button type="button" 
                        class="button button-primary mt-run-maintenance"
                        data-category="cache"
                        data-operation="<?php echo esc_attr($op_key); ?>"
                        <?php echo !empty($operation['confirm']) ? 'data-confirm="' . esc_attr($operation['confirm']) . '"' : ''; ?>>
                    <?php echo esc_html($operation['button_text'] ?? __('Execute', 'mobility-trailblazers')); ?>
                </button>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Data Management -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Data Management', 'mobility-trailblazers'); ?></h3>
        <div class="mt-tools-grid">
            <!-- Export Data -->
            <div class="mt-tool-card">
                <div class="mt-tool-icon">
                    <span class="dashicons dashicons-download"></span>
                </div>
                <h4><?php esc_html_e('Export All Data', 'mobility-trailblazers'); ?></h4>
                <p><?php esc_html_e('Export all plugin data including evaluations, assignments, and settings.', 'mobility-trailblazers'); ?></p>
                <div class="mt-tool-options">
                    <label>
                        <input type="checkbox" class="mt-export-option" data-option="include_candidates" checked>
                        <?php esc_html_e('Include Candidates', 'mobility-trailblazers'); ?>
                    </label>
                    <label>
                        <input type="checkbox" class="mt-export-option" data-option="include_jury" checked>
                        <?php esc_html_e('Include Jury Members', 'mobility-trailblazers'); ?>
                    </label>
                    <label>
                        <input type="checkbox" class="mt-export-option" data-option="include_evaluations" checked>
                        <?php esc_html_e('Include Evaluations', 'mobility-trailblazers'); ?>
                    </label>
                    <label>
                        <input type="checkbox" class="mt-export-option" data-option="include_settings" checked>
                        <?php esc_html_e('Include Settings', 'mobility-trailblazers'); ?>
                    </label>
                </div>
                <button type="button" 
                        class="button button-primary mt-run-maintenance"
                        data-category="import_export"
                        data-operation="export_all">
                    <?php esc_html_e('Export Data', 'mobility-trailblazers'); ?>
                </button>
            </div>

            <!-- Import Data -->
            <div class="mt-tool-card">
                <div class="mt-tool-icon">
                    <span class="dashicons dashicons-upload"></span>
                </div>
                <h4><?php esc_html_e('Import Data', 'mobility-trailblazers'); ?></h4>
                <p><?php esc_html_e('Import previously exported plugin data from a backup file.', 'mobility-trailblazers'); ?></p>
                <div class="mt-tool-upload">
                    <input type="file" 
                           id="mt-import-file" 
                           accept=".json,.sql,.csv"
                           class="mt-file-input">
                    <label for="mt-import-file" class="button">
                        <?php esc_html_e('Choose File', 'mobility-trailblazers'); ?>
                    </label>
                    <span class="mt-file-name"><?php esc_html_e('No file selected', 'mobility-trailblazers'); ?></span>
                </div>
                <button type="button" 
                        class="button mt-run-maintenance"
                        data-category="import_export"
                        data-operation="import_data"
                        disabled>
                    <?php esc_html_e('Import Data', 'mobility-trailblazers'); ?>
                </button>
            </div>

            <!-- Backup Database -->
            <div class="mt-tool-card">
                <div class="mt-tool-icon">
                    <span class="dashicons dashicons-backup"></span>
                </div>
                <h4><?php esc_html_e('Backup Plugin Tables', 'mobility-trailblazers'); ?></h4>
                <p><?php esc_html_e('Create a SQL backup of all plugin database tables.', 'mobility-trailblazers'); ?></p>
                <?php
                // Get last backup info
                $last_backup = get_option('mt_last_backup_time');
                if ($last_backup):
                ?>
                <div class="mt-tool-info">
                    <span class="dashicons dashicons-clock"></span>
                    <?php 
                    printf(
                        esc_html__('Last backup: %s', 'mobility-trailblazers'),
                        esc_html(human_time_diff($last_backup) . ' ago')
                    );
                    ?>
                </div>
                <?php endif; ?>
                <button type="button" 
                        class="button button-primary mt-run-maintenance"
                        data-category="import_export"
                        data-operation="backup_tables">
                    <?php esc_html_e('Create Backup', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Reset Operations -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Reset Operations', 'mobility-trailblazers'); ?></h3>
        <div class="mt-danger-notice">
            <span class="dashicons dashicons-warning"></span>
            <?php esc_html_e('These operations will permanently delete data. Please ensure you have backups before proceeding.', 'mobility-trailblazers'); ?>
        </div>
        <div class="mt-tools-grid">
            <?php if (isset($operations['reset']['operations']) && is_array($operations['reset']['operations'])): ?>
                <?php foreach ($operations['reset']['operations'] as $op_key => $operation): 
                    if (!is_array($operation)) continue;
                    $is_factory_reset = ($op_key === 'factory_reset');
                ?>
            <div class="mt-tool-card <?php echo $is_factory_reset ? 'mt-tool-danger' : 'mt-tool-warning'; ?>">
                <div class="mt-tool-icon">
                    <span class="dashicons dashicons-<?php echo esc_attr($operation['icon'] ?? 'warning'); ?>"></span>
                </div>
                <h4><?php echo esc_html(isset($operation['title']) ? $operation['title'] : 'Operation'); ?></h4>
                <p><?php echo esc_html(isset($operation['description']) ? $operation['description'] : ''); ?></p>
                
                <?php if ($is_factory_reset): ?>
                <div class="mt-tool-password">
                    <label for="mt-admin-password">
                        <?php esc_html_e('Enter admin password to confirm:', 'mobility-trailblazers'); ?>
                    </label>
                    <input type="password" 
                           id="mt-admin-password" 
                           class="mt-password-input"
                           placeholder="<?php esc_attr_e('Admin password', 'mobility-trailblazers'); ?>">
                </div>
                <?php endif; ?>
                
                <button type="button" 
                        class="button <?php echo $is_factory_reset ? 'button-danger' : 'button-secondary'; ?> mt-run-maintenance"
                        data-category="reset"
                        data-operation="<?php echo esc_attr($op_key); ?>"
                        data-confirm="<?php echo esc_attr(isset($operation['confirm']) ? $operation['confirm'] : ''); ?>"
                        <?php echo $is_factory_reset ? 'data-requires-password="true"' : ''; ?>
                        <?php echo ($environment === 'production' && !empty($operation['block_production'])) ? 'disabled' : ''; ?>>
                    <?php echo esc_html($operation['button_text'] ?? __('Reset', 'mobility-trailblazers')); ?>
                </button>
                
                <?php if ($environment === 'production' && !empty($operation['block_production'])): ?>
                <div class="mt-tool-blocked">
                    <span class="dashicons dashicons-lock"></span>
                    <?php esc_html_e('Blocked in production', 'mobility-trailblazers'); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scheduled Tasks -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Scheduled Tasks', 'mobility-trailblazers'); ?></h3>
        <div class="mt-scheduled-tasks">
            <?php
            // Get scheduled cron jobs related to our plugin
            $cron_jobs = _get_cron_array();
            $plugin_jobs = [];
            
            foreach ($cron_jobs as $timestamp => $cron) {
                foreach ($cron as $hook => $args) {
                    if (strpos($hook, 'mt_') === 0) {
                        $plugin_jobs[] = [
                            'hook' => $hook,
                            'next_run' => $timestamp,
                            'schedule' => $args[key($args)]['schedule'] ?? 'once'
                        ];
                    }
                }
            }
            
            if (empty($plugin_jobs)):
            ?>
            <p><?php esc_html_e('No scheduled tasks found.', 'mobility-trailblazers'); ?></p>
            <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Task', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Schedule', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Next Run', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Actions', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plugin_jobs as $job): ?>
                    <tr>
                        <td><code><?php echo esc_html($job['hook']); ?></code></td>
                        <td><?php echo esc_html(ucfirst($job['schedule'])); ?></td>
                        <td>
                            <?php 
                            if ($job['next_run'] > time()) {
                                echo esc_html(human_time_diff(time(), $job['next_run']) . ' from now');
                            } else {
                                echo '<span class="mt-overdue">' . esc_html__('Overdue', 'mobility-trailblazers') . '</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <button type="button" 
                                    class="button button-small mt-run-cron"
                                    data-hook="<?php echo esc_attr($job['hook']); ?>">
                                <?php esc_html_e('Run Now', 'mobility-trailblazers'); ?>
                            </button>
                            <button type="button" 
                                    class="button button-small mt-delete-cron"
                                    data-hook="<?php echo esc_attr($job['hook']); ?>"
                                    data-timestamp="<?php echo esc_attr($job['next_run']); ?>">
                                <?php esc_html_e('Delete', 'mobility-trailblazers'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>