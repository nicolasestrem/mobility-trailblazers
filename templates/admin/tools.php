<?php
/**
 * Admin Tools Page Template
 *
 * @package MobilityTrailblazers
 * @since 2.2.7
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
}

// Handle form submissions
$message = '';
$message_type = '';

if (isset($_POST['mt_sync_evaluations']) && wp_verify_nonce($_POST['mt_tools_nonce'], 'mt_tools_action')) {
    $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
    $stats = $evaluation_repo->sync_with_assignments();
    
    $message = sprintf(
        __('Sync completed. Found %d orphaned evaluations, deleted %d.', 'mobility-trailblazers'),
        $stats['orphaned_found'],
        $stats['orphaned_deleted']
    );
    
    if (!empty($stats['errors'])) {
        $message .= ' ' . __('Some errors occurred during sync.', 'mobility-trailblazers');
        $message_type = 'warning';
    } else {
        $message_type = 'success';
    }
}

// Get statistics for display
$evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
$assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();

// Check for orphaned evaluations
global $wpdb;
$orphaned_count = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}mt_evaluations e
     WHERE NOT EXISTS (
         SELECT 1 FROM {$wpdb->prefix}mt_jury_assignments a 
         WHERE a.jury_member_id = e.jury_member_id 
         AND a.candidate_id = e.candidate_id
     )"
);

$evaluation_stats = $evaluation_repo->get_statistics();
$assignment_stats = $assignment_repo->get_statistics();
?>

<div class="wrap">
    <h1><?php _e('Mobility Trailblazers Tools', 'mobility-trailblazers'); ?></h1>
    
    <?php if ($message): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2><?php _e('Database Health Status', 'mobility-trailblazers'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <th><?php _e('Total Evaluations', 'mobility-trailblazers'); ?></th>
                    <td><?php echo esc_html($evaluation_stats['total']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Total Assignments', 'mobility-trailblazers'); ?></th>
                    <td><?php echo esc_html($assignment_stats['total_assignments']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Orphaned Evaluations', 'mobility-trailblazers'); ?></th>
                    <td>
                        <?php if ($orphaned_count > 0): ?>
                            <span style="color: #d63638; font-weight: bold;">
                                <?php echo esc_html($orphaned_count); ?>
                                <?php _e('(Action required)', 'mobility-trailblazers'); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #00a32a;">
                                <?php echo esc_html($orphaned_count); ?>
                                <?php _e('(Healthy)', 'mobility-trailblazers'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h2><?php _e('Database Maintenance', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Use these tools to maintain database integrity.', 'mobility-trailblazers'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('mt_tools_action', 'mt_tools_nonce'); ?>
            
            <h3><?php _e('Sync Evaluations with Assignments', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('This will remove evaluations that no longer have corresponding assignments.', 'mobility-trailblazers'); ?></p>
            
            <?php if ($orphaned_count > 0): ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php 
                        printf(
                            __('Found %d orphaned evaluations that will be deleted.', 'mobility-trailblazers'),
                            $orphaned_count
                        );
                        ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="notice notice-success inline">
                    <p><?php _e('No orphaned evaluations found. Database is healthy.', 'mobility-trailblazers'); ?></p>
                </div>
            <?php endif; ?>
            
            <p class="submit">
                <input type="submit" 
                       name="mt_sync_evaluations" 
                       class="button button-primary" 
                       value="<?php esc_attr_e('Sync Evaluations', 'mobility-trailblazers'); ?>"
                       <?php if ($orphaned_count == 0) echo 'disabled'; ?>
                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to sync evaluations? This will delete orphaned evaluations.', 'mobility-trailblazers'); ?>');">
                <?php if ($orphaned_count == 0): ?>
                    <span class="description"><?php _e('No sync needed', 'mobility-trailblazers'); ?></span>
                <?php endif; ?>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2><?php _e('Quick Actions', 'mobility-trailblazers'); ?></h2>
        <p>
            <a href="<?php echo admin_url('admin.php?page=mt-evaluations'); ?>" class="button">
                <?php _e('View Evaluations', 'mobility-trailblazers'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=mt-assignments'); ?>" class="button">
                <?php _e('View Assignments', 'mobility-trailblazers'); ?>
            </a>
            <a href="<?php echo admin_url('edit.php?post_type=mt_candidate'); ?>" class="button">
                <?php _e('View Candidates', 'mobility-trailblazers'); ?>
            </a>
            <a href="<?php echo admin_url('edit.php?post_type=mt_jury_member'); ?>" class="button">
                <?php _e('View Jury Members', 'mobility-trailblazers'); ?>
            </a>
        </p>
    </div>
</div>
