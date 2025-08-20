<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get database health utility
$db_health = new \MobilityTrailblazers\Utilities\MT_Database_Health();

// Get database stats with defaults
$db_stats = $db_health->get_database_stats();
if (!is_array($db_stats)) {
    $db_stats = [
        'total_tables' => 0,
        'total_rows' => 0,
        'total_size_formatted' => 'N/A',
        'plugin_tables' => 0
    ];
}

$connection_info = $db_health->get_connection_info();
if (!is_array($connection_info)) {
    $connection_info = [
        'host' => 'Unknown',
        'database' => 'Unknown',
        'server_version' => 'Unknown',
        'charset' => 'Unknown',
        'collation' => 'Unknown'
    ];
}

$all_tables = $db_health->check_all_tables();
if (!is_array($all_tables)) {
    $all_tables = [];
}
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
                <span><?php echo esc_html(isset($connection_info['server_version']) ? $connection_info['server_version'] : 'Unknown'); ?></span>
            </div>
            <div class="mt-info-item">
                <strong><?php esc_html_e('Charset:', 'mobility-trailblazers'); ?></strong>
                <span><?php echo esc_html($connection_info['charset']); ?></span>
            </div>
            <div class="mt-info-item">
                <strong><?php esc_html_e('Collation:', 'mobility-trailblazers'); ?></strong>
                <span><?php echo esc_html(isset($connection_info['collation']) ? $connection_info['collation'] : 'Unknown'); ?></span>
            </div>
        </div>
    </div>

    <!-- Database Statistics -->
    <div class="mt-debug-section">
        <h3><?php esc_html_e('Database Statistics', 'mobility-trailblazers'); ?></h3>
        <div class="mt-stats-grid">
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html(isset($db_stats['total_tables']) ? $db_stats['total_tables'] : 0); ?></div>
                <div class="mt-stat-label"><?php esc_html_e('Total Tables', 'mobility-trailblazers'); ?></div>
            </div>
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html(number_format(isset($db_stats['total_rows']) ? $db_stats['total_rows'] : 0)); ?></div>
                <div class="mt-stat-label"><?php esc_html_e('Total Rows', 'mobility-trailblazers'); ?></div>
            </div>
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html(isset($db_stats['total_size_formatted']) ? $db_stats['total_size_formatted'] : 'N/A'); ?></div>
                <div class="mt-stat-label"><?php esc_html_e('Database Size', 'mobility-trailblazers'); ?></div>
            </div>
            <div class="mt-stat-card">
                <div class="mt-stat-value"><?php echo esc_html(isset($db_stats['plugin_tables']) ? $db_stats['plugin_tables'] : 0); ?></div>
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
                    <?php if (!empty($all_tables)): ?>
                        <?php foreach ($all_tables as $table): 
                            // Ensure table data is properly structured
                            if (!is_array($table)) continue;
                            $name = isset($table['name']) ? $table['name'] : '';
                            $engine = isset($table['engine']) ? $table['engine'] : 'Unknown';
                            $rows = isset($table['rows']) ? $table['rows'] : 0;
                            $size_formatted = isset($table['size_formatted']) ? $table['size_formatted'] : 'N/A';
                            $fragmentation = isset($table['fragmentation']) ? $table['fragmentation'] : 0;
                            $status = isset($table['status']) ? $table['status'] : 'unknown';
                            $is_plugin_table = isset($table['is_plugin_table']) ? $table['is_plugin_table'] : false;
                            
                            if (empty($name)) continue;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($name); ?></strong>
                                <?php if ($is_plugin_table): ?>
                                <span class="dashicons dashicons-admin-plugins" title="<?php esc_attr_e('Plugin Table', 'mobility-trailblazers'); ?>"></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($engine); ?></td>
                            <td><?php echo esc_html(number_format($rows)); ?></td>
                            <td><?php echo esc_html($size_formatted); ?></td>
                            <td>
                                <?php 
                                $class = $fragmentation > 30 ? 'high' : ($fragmentation > 10 ? 'medium' : 'low');
                                ?>
                                <span class="fragmentation-<?php echo esc_attr($class); ?>">
                                    <?php echo esc_html($fragmentation . '%'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($status === 'healthy'): ?>
                                    <span class="status-healthy">✓ <?php esc_html_e('Healthy', 'mobility-trailblazers'); ?></span>
                                <?php elseif ($status === 'needs_optimization'): ?>
                                    <span class="status-warning">⚠ <?php esc_html_e('Needs Optimization', 'mobility-trailblazers'); ?></span>
                                <?php else: ?>
                                    <span class="status-error">✗ <?php esc_html_e('Issues Detected', 'mobility-trailblazers'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" 
                                        class="button button-small mt-optimize-table" 
                                        data-table="<?php echo esc_attr($name); ?>">
                                    <?php esc_html_e('Optimize', 'mobility-trailblazers'); ?>
                                </button>
                                <button type="button" 
                                        class="button button-small mt-analyze-table" 
                                        data-table="<?php echo esc_attr($name); ?>">
                                    <?php esc_html_e('Analyze', 'mobility-trailblazers'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7"><?php esc_html_e('No tables found or database connection error.', 'mobility-trailblazers'); ?></td>
                        </tr>
                    <?php endif; ?>
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
            
            <?php 
            // REMOVED: Delete All Candidates button - dangerous operation removed 2025-08-20
            // This feature was permanently disabled for security reasons
            if (defined('MT_DEV_TOOLS') && MT_DEV_TOOLS && defined('WP_DEBUG') && WP_DEBUG) : ?>
            <!-- Delete All Candidates - Only visible in development with MT_DEV_TOOLS enabled -->
            <div class="mt-operation-card" style="opacity: 0.5; pointer-events: none;">
                <h4><?php esc_html_e('Delete All Candidates (Disabled)', 'mobility-trailblazers'); ?></h4>
                <p><?php esc_html_e('This dangerous operation has been permanently disabled for security reasons.', 'mobility-trailblazers'); ?></p>
                <button type="button" class="button button-danger" disabled>
                    <?php esc_html_e('Function Removed', 'mobility-trailblazers'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Slow Queries -->
    <?php 
    $slow_queries = $db_health->get_slow_queries(10);
    if (!empty($slow_queries) && is_array($slow_queries)): 
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
                    <?php foreach ($slow_queries as $query): 
                        if (!is_array($query)) continue;
                        $query_text = isset($query['query']) ? $query['query'] : '';
                        $time = isset($query['time']) ? $query['time'] : 'N/A';
                        $rows_examined = isset($query['rows_examined']) ? $query['rows_examined'] : 'N/A';
                        
                        if (empty($query_text)) continue;
                    ?>
                    <tr>
                        <td>
                            <code class="mt-query-preview">
                                <?php echo esc_html(substr($query_text, 0, 100) . (strlen($query_text) > 100 ? '...' : '')); ?>
                            </code>
                        </td>
                        <td><?php echo esc_html($time); ?></td>
                        <td><?php echo esc_html($rows_examined); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>