<?php
/**
 * Database Fix Page Template
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Database Fix', 'mobility-trailblazers'); ?></h1>
    
    <?php if (isset($message)): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="mt-database-status">
        <h2><?php _e('Database Table Status', 'mobility-trailblazers'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Table Name', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Columns', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($table_status as $table_name => $status): ?>
                    <tr>
                        <td><strong><?php echo esc_html($table_name); ?></strong></td>
                        <td>
                            <?php if ($status['exists']): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                <?php _e('Exists', 'mobility-trailblazers'); ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-no-alt" style="color: red;"></span>
                                <?php _e('Missing', 'mobility-trailblazers'); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($status['exists'] && isset($status['columns'])): ?>
                                <?php echo esc_html($status['columns']); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-database-actions">
        <h2><?php _e('Database Actions', 'mobility-trailblazers'); ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('mt_fix_database', 'mt_database_fix_nonce'); ?>
            
            <p>
                <?php _e('If any tables are missing, click the button below to create them:', 'mobility-trailblazers'); ?>
            </p>
            
            <p class="submit">
                <input type="submit" name="mt_fix_database" class="button button-primary" value="<?php _e('Create Missing Tables', 'mobility-trailblazers'); ?>">
            </p>
        </form>
    </div>
    
    <div class="mt-database-info">
        <h2><?php _e('Database Information', 'mobility-trailblazers'); ?></h2>
        
        <p>
            <?php _e('This page allows you to fix database issues that may occur during plugin installation or updates.', 'mobility-trailblazers'); ?>
        </p>
        
        <p>
            <strong><?php _e('Required Tables:', 'mobility-trailblazers'); ?></strong>
        </p>
        
        <ul>
            <li><code>wp_mt_jury_assignments</code> - <?php _e('Stores jury member assignments to candidates', 'mobility-trailblazers'); ?></li>
            <li><code>wp_mt_evaluations</code> - <?php _e('Stores jury member evaluations of candidates', 'mobility-trailblazers'); ?></li>
            <li><code>wp_mt_votes</code> - <?php _e('Stores voting data', 'mobility-trailblazers'); ?></li>
            <li><code>wp_mt_candidate_scores</code> - <?php _e('Stores candidate scoring data', 'mobility-trailblazers'); ?></li>
            <li><code>wp_vote_reset_logs</code> - <?php _e('Stores vote reset logs', 'mobility-trailblazers'); ?></li>
            <li><code>wp_mt_vote_backups</code> - <?php _e('Stores vote backup data', 'mobility-trailblazers'); ?></li>
        </ul>
    </div>
</div>

<style>
.mt-database-status,
.mt-database-actions,
.mt-database-info {
    margin-top: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-database-status h2,
.mt-database-actions h2,
.mt-database-info h2 {
    margin-top: 0;
    margin-bottom: 15px;
}

.mt-database-info ul {
    margin-left: 20px;
}

.mt-database-info code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
}
</style> 