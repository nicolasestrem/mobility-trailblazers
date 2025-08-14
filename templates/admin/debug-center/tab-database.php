<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get database health utility
$db_health = new \MobilityTrailblazers\Utilities\MT_Database_Health();

// Get database stats
$db_stats = $db_health->get_database_stats();
$connection_info = $db_health->get_connection_info();
$all_tables = $db_health->check_all_tables();
?>

<div class="mt-debug-database">
    <div class="mt-debug-header">
        <h2><?php esc_html_e('Database Health & Optimization', 'mobility-trailblazers'); ?></h2>
        <p class="description">
            <?php esc_html_e('Monitor database health, analyze table performance, and optimize database operations.', 'mobility-trailblazers'); ?>
        </p>
    </div>

    <!-- Connection Info -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Database Connection', 'mobility-trailblazers'); ?></h3>
        <div class="mt-info-grid">
            <div class="mt-info-item">
                <strong><?php esc_html_e('Host:', 'mobility-trailblazers'); ?></strong>
                <span><?php echo esc_html($connection_info['host']); ?></span>
            </div>
            <div class="mt-info-item">
                <strong><?php esc_html_e('Database:', 'mobility-trailblazers'); ?></strong>
                <span><?php echo esc_html($connection_info['database']); ?></span>
            </div>
            <div class="mt-info-item">
                <strong><?php esc_html_e('Version:', 'mobility-trailblazers'); ?></strong>
                <span><?php echo esc_html($connection_info['server_version']); ?></span>
            </div>
            <div class="mt-info-item">
                <strong><?php esc_html_e('Charset:', 'mobility-trailblazers'); ?></strong>
                <span><?php echo esc_html($connection_info['charset']); ?></span>
            </div>
            <div class="mt-info-item">
                <strong><?php esc_html_e('Collation:', 'mobility-trailblazers'); ?></strong>
                <span><?php echo esc_html($connection_info['collation']); ?></span>
            </div>
        </div>
    </div>

    <!-- Database Statistics -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Database Statistics', 'mobility-trailblazers'); ?></h3>
        <div class="mt-stats-grid">
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html($db_stats['total_tables']); ?></div>
                <div class="mt-stat-label"><?php esc_html_e('Total Tables', 'mobility-trailblazers'); ?></div>
            </div>
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html(number_format($db_stats['total_rows'])); ?></div>
                <div class="mt-stat-label"><?php esc_html_e('Total Rows', 'mobility-trailblazers'); ?></div>
            </div>
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html($db_stats['total_size_formatted']); ?></div>
                <div class="mt-stat-label"><?php esc_html_e('Database Size', 'mobility-trailblazers'); ?></div>
            </div>
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html($db_stats['plugin_tables']); ?></div>
                <div class="mt-stat-label"><?php esc_html_e('Plugin Tables', 'mobility-trailblazers'); ?></div>
            </div>
        </div>
    </div>

    <!-- Table Health Check -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Table Health Check', 'mobility-trailblazers'); ?></h3>
        <div class="mt-table-health">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Table Name', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Engine', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Rows', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Size', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Fragmentation', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Status', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Actions', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_tables as $table): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($table['name']); ?></strong>
                            <?php if ($table['is_plugin_table']): ?>
                            <span class="dashicons dashicons-admin-plugins" title="<?php esc_attr_e('Plugin Table', 'mobility-trailblazers'); ?>"></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($table['engine']); ?></td>
                        <td><?php echo esc_html(number_format($table['rows'])); ?></td>
                        <td><?php echo esc_html($table['size_formatted']); ?></td>
                        <td>
                            <?php 
                            $fragmentation = $table['fragmentation'];
                            $class = $fragmentation > 30 ? 'high' : ($fragmentation > 10 ? 'medium' : 'low');
                            ?>
                            <span class="fragmentation-<?php echo esc_attr($class); ?>">
                                <?php echo esc_html($fragmentation . '%'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($table['status'] === 'healthy'): ?>
                                <span class="status-healthy">✓ <?php esc_html_e('Healthy', 'mobility-trailblazers'); ?></span>
                            <?php elseif ($table['status'] === 'needs_optimization'): ?>
                                <span class="status-warning">⚠ <?php esc_html_e('Needs Optimization', 'mobility-trailblazers'); ?></span>
                            <?php else: ?>
                                <span class="status-error">✗ <?php esc_html_e('Issues Detected', 'mobility-trailblazers'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" 
                                    class="button button-small mt-optimize-table" 
                                    data-table="<?php echo esc_attr($table['name']); ?>">
                                <?php esc_html_e('Optimize', 'mobility-trailblazers'); ?>
                            </button>
                            <button type="button" 
                                    class="button button-small mt-analyze-table" 
                                    data-table="<?php echo esc_attr($table['name']); ?>">
                                <?php esc_html_e('Analyze', 'mobility-trailblazers'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Database Operations -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Database Operations', 'mobility-trailblazers'); ?></h3>
        <div class="mt-operations-grid">
            <div class="mt-operation-card">
                <h4><?php esc_html_e('Optimize All Tables', 'mobility-trailblazers'); ?></h4>
                <p><?php esc_html_e('Optimize all database tables to reclaim space and improve performance.', 'mobility-trailblazers'); ?></p>
                <button type="button" class="button button-primary mt-run-maintenance" 
                        data-category="database" 
                        data-operation="optimize_all">
                    <?php esc_html_e('Optimize All', 'mobility-trailblazers'); ?>
                </button>
            </div>
            
            <div class="mt-operation-card">
                <h4><?php esc_html_e('Repair Tables', 'mobility-trailblazers'); ?></h4>
                <p><?php esc_html_e('Check and repair any corrupted database tables.', 'mobility-trailblazers'); ?></p>
                <button type="button" class="button mt-run-maintenance" 
                        data-category="database" 
                        data-operation="repair_tables">
                    <?php esc_html_e('Repair Tables', 'mobility-trailblazers'); ?>
                </button>
            </div>
            
            <div class="mt-operation-card">
                <h4><?php esc_html_e('Clean Orphaned Data', 'mobility-trailblazers'); ?></h4>
                <p><?php esc_html_e('Remove orphaned post meta, comment meta, and other unused data.', 'mobility-trailblazers'); ?></p>
                <button type="button" class="button mt-run-maintenance" 
                        data-category="database" 
                        data-operation="clean_orphaned"
                        data-confirm="<?php esc_attr_e('This will permanently delete orphaned data. Continue?', 'mobility-trailblazers'); ?>">
                    <?php esc_html_e('Clean Data', 'mobility-trailblazers'); ?>
                </button>
            </div>
            
            <div class="mt-operation-card">
                <h4><?php esc_html_e('Clear Transients', 'mobility-trailblazers'); ?></h4>
                <p><?php esc_html_e('Remove all expired and orphaned transient data.', 'mobility-trailblazers'); ?></p>
                <button type="button" class="button mt-run-maintenance" 
                        data-category="cache" 
                        data-operation="clear_transients">
                    <?php esc_html_e('Clear Transients', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Slow Queries -->
    <?php 
    $slow_queries = $db_health->get_slow_queries(10);
    if (!empty($slow_queries)): 
    ?>
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Slow Queries', 'mobility-trailblazers'); ?></h3>
        <div class="mt-slow-queries">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Query', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Time (s)', 'mobility-trailblazers'); ?></th>
                        <th><?php esc_html_e('Rows Examined', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slow_queries as $query): ?>
                    <tr>
                        <td>
                            <code class="mt-query-preview">
                                <?php echo esc_html(substr($query['query'], 0, 100) . (strlen($query['query']) > 100 ? '...' : '')); ?>
                            </code>
                        </td>
                        <td><?php echo esc_html($query['time']); ?></td>
                        <td><?php echo esc_html($query['rows_examined']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>