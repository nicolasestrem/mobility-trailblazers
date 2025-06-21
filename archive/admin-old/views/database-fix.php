<?php
/**
 * Database Fix Admin View
 *
 * @package MobilityTrailblazers
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
}

// Handle form submission
$message = '';
$message_type = '';

if (isset($_POST['mt_fix_database']) && wp_verify_nonce($_POST['mt_fix_database_nonce'], 'mt_fix_database')) {
    // Force create tables
    require_once MT_PLUGIN_DIR . 'includes/class-database.php';
    $database = new MT_Database();
    $database->force_create_tables();
    
    $message = __('Database tables have been recreated successfully.', 'mobility-trailblazers');
    $message_type = 'success';
}

// Check current table status
global $wpdb;

$tables = array(
    'mt_votes' => $wpdb->prefix . 'mt_votes',
    'mt_candidate_scores' => $wpdb->prefix . 'mt_candidate_scores',
    'mt_evaluations' => $wpdb->prefix . 'mt_evaluations',
    'mt_jury_assignments' => $wpdb->prefix . 'mt_jury_assignments',
    'vote_reset_logs' => $wpdb->prefix . 'vote_reset_logs',
    'mt_vote_backups' => $wpdb->prefix . 'mt_vote_backups',
);

$table_status = array();
foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    $columns = 0;
    if ($exists) {
        $columns = count($wpdb->get_results("SHOW COLUMNS FROM $table"));
    }
    $table_status[$name] = array(
        'exists' => $exists,
        'columns' => $columns,
        'full_name' => $table
    );
}

// Check for missing function
$function_exists = function_exists('mt_is_jury_member');
?>

<div class="wrap">
    <h1><?php _e('Database Fix', 'mobility-trailblazers'); ?></h1>
    
    <?php if ($message): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2><?php _e('Database Status', 'mobility-trailblazers'); ?></h2>
        
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Table Name', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Columns', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Full Name', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($table_status as $name => $status): ?>
                    <tr>
                        <td><?php echo esc_html($name); ?></td>
                        <td>
                            <?php if ($status['exists']): ?>
                                <span style="color: green;">✓ <?php _e('Exists', 'mobility-trailblazers'); ?></span>
                            <?php else: ?>
                                <span style="color: red;">✗ <?php _e('Missing', 'mobility-trailblazers'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $status['exists'] ? $status['columns'] : '-'; ?></td>
                        <td><code><?php echo esc_html($status['full_name']); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3><?php _e('Function Status', 'mobility-trailblazers'); ?></h3>
        <p>
            <strong>mt_is_jury_member():</strong> 
            <?php if ($function_exists): ?>
                <span style="color: green;">✓ <?php _e('Function exists', 'mobility-trailblazers'); ?></span>
            <?php else: ?>
                <span style="color: red;">✗ <?php _e('Function missing', 'mobility-trailblazers'); ?></span>
            <?php endif; ?>
        </p>
    </div>
    
    <div class="card">
        <h2><?php _e('Fix Database Issues', 'mobility-trailblazers'); ?></h2>
        
        <p><?php _e('Click the button below to recreate all database tables. This will not delete existing data.', 'mobility-trailblazers'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('mt_fix_database', 'mt_fix_database_nonce'); ?>
            <p class="submit">
                <input type="submit" name="mt_fix_database" class="button button-primary" value="<?php _e('Fix Database Tables', 'mobility-trailblazers'); ?>" />
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2><?php _e('Additional Information', 'mobility-trailblazers'); ?></h2>
        
        <p><strong><?php _e('Database Version:', 'mobility-trailblazers'); ?></strong> <?php echo get_option('mt_db_version', 'Not set'); ?></p>
        <p><strong><?php _e('Plugin Version:', 'mobility-trailblazers'); ?></strong> <?php echo MT_PLUGIN_VERSION; ?></p>
        <p><strong><?php _e('WordPress Version:', 'mobility-trailblazers'); ?></strong> <?php echo get_bloginfo('version'); ?></p>
        <p><strong><?php _e('PHP Version:', 'mobility-trailblazers'); ?></strong> <?php echo PHP_VERSION; ?></p>
        <p><strong><?php _e('MySQL Version:', 'mobility-trailblazers'); ?></strong> <?php echo $wpdb->db_version(); ?></p>
    </div>
</div>