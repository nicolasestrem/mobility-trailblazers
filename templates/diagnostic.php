<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Check if user has permission
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'mobility-trailblazers'));
}

// Debug information
error_log('Starting diagnostic page');
error_log('MT_PLUGIN_PATH: ' . MT_PLUGIN_PATH);
error_log('MT_PLUGIN_VERSION: ' . MT_PLUGIN_VERSION);

// Get system information
$system_info = array(
    'WordPress Version' => get_bloginfo('version'),
    'PHP Version' => PHP_VERSION,
    'MySQL Version' => $wpdb->get_var('SELECT VERSION()'),
    'Plugin Version' => MT_PLUGIN_VERSION,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'],
    'Memory Limit' => ini_get('memory_limit'),
    'Max Upload Size' => ini_get('upload_max_filesize'),
    'Max Post Size' => ini_get('post_max_size'),
    'Max Execution Time' => ini_get('max_execution_time') . ' seconds'
);

error_log('System info collected: ' . print_r($system_info, true));

// Check database tables
$tables = array(
    'mt_votes' => $wpdb->prefix . 'mt_votes',
    'mt_vote_audit_log' => $wpdb->prefix . 'mt_vote_audit_log',
    'vote_reset_logs' => $wpdb->prefix . 'vote_reset_logs'
);

$table_status = array();
foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    $table_status[$name] = $exists;
    error_log("Table $name ($table) exists: " . ($exists ? 'yes' : 'no'));
}

// Check post types
$post_types = array('mt_candidate', 'mt_jury');
$post_type_status = array();
foreach ($post_types as $post_type) {
    $exists = post_type_exists($post_type);
    $post_type_status[$post_type] = $exists;
    error_log("Post type $post_type exists: " . ($exists ? 'yes' : 'no'));
}

// Check taxonomies
$taxonomies = array('mt_category', 'mt_phase', 'mt_status', 'mt_award_year');
$taxonomy_status = array();
foreach ($taxonomies as $taxonomy) {
    $exists = taxonomy_exists($taxonomy);
    $taxonomy_status[$taxonomy] = $exists;
    error_log("Taxonomy $taxonomy exists: " . ($exists ? 'yes' : 'no'));
}

// Check roles
$roles = array('mt_jury_member', 'mt_admin');
$role_status = array();
foreach ($roles as $role) {
    $exists = get_role($role) !== null;
    $role_status[$role] = $exists;
    error_log("Role $role exists: " . ($exists ? 'yes' : 'no'));
}

// Check file permissions
$directories = array(
    'templates' => MT_PLUGIN_PATH . 'templates',
    'includes' => MT_PLUGIN_PATH . 'includes',
    'assets' => MT_PLUGIN_PATH . 'assets'
);

$directory_status = array();
foreach ($directories as $name => $dir) {
    $accessible = is_dir($dir) && is_readable($dir);
    $directory_status[$name] = $accessible;
    error_log("Directory $name ($dir) accessible: " . ($accessible ? 'yes' : 'no'));
}

error_log('All checks completed');
?>

<div class="wrap">
    <h1><?php _e('System Diagnostic', 'mobility-trailblazers'); ?></h1>

    <div class="mt-diagnostic-container">
        <!-- System Information -->
        <div class="mt-diagnostic-section">
            <h2><?php _e('System Information', 'mobility-trailblazers'); ?></h2>
            <table class="widefat">
                <tbody>
                    <?php foreach ($system_info as $key => $value): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($key); ?></th>
                            <td><?php echo esc_html($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Database Tables -->
        <div class="mt-diagnostic-section">
            <h2><?php _e('Database Tables', 'mobility-trailblazers'); ?></h2>
            <table class="widefat">
                <tbody>
                    <?php foreach ($table_status as $table => $exists): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($table); ?></th>
                            <td>
                                <?php if ($exists): ?>
                                    <span class="mt-status-ok"><?php _e('OK', 'mobility-trailblazers'); ?></span>
                                <?php else: ?>
                                    <span class="mt-status-error"><?php _e('Missing', 'mobility-trailblazers'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Post Types -->
        <div class="mt-diagnostic-section">
            <h2><?php _e('Post Types', 'mobility-trailblazers'); ?></h2>
            <table class="widefat">
                <tbody>
                    <?php foreach ($post_type_status as $post_type => $exists): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($post_type); ?></th>
                            <td>
                                <?php if ($exists): ?>
                                    <span class="mt-status-ok"><?php _e('OK', 'mobility-trailblazers'); ?></span>
                                <?php else: ?>
                                    <span class="mt-status-error"><?php _e('Missing', 'mobility-trailblazers'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Taxonomies -->
        <div class="mt-diagnostic-section">
            <h2><?php _e('Taxonomies', 'mobility-trailblazers'); ?></h2>
            <table class="widefat">
                <tbody>
                    <?php foreach ($taxonomy_status as $taxonomy => $exists): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($taxonomy); ?></th>
                            <td>
                                <?php if ($exists): ?>
                                    <span class="mt-status-ok"><?php _e('OK', 'mobility-trailblazers'); ?></span>
                                <?php else: ?>
                                    <span class="mt-status-error"><?php _e('Missing', 'mobility-trailblazers'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Roles -->
        <div class="mt-diagnostic-section">
            <h2><?php _e('User Roles', 'mobility-trailblazers'); ?></h2>
            <table class="widefat">
                <tbody>
                    <?php foreach ($role_status as $role => $exists): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($role); ?></th>
                            <td>
                                <?php if ($exists): ?>
                                    <span class="mt-status-ok"><?php _e('OK', 'mobility-trailblazers'); ?></span>
                                <?php else: ?>
                                    <span class="mt-status-error"><?php _e('Missing', 'mobility-trailblazers'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Directory Permissions -->
        <div class="mt-diagnostic-section">
            <h2><?php _e('Directory Permissions', 'mobility-trailblazers'); ?></h2>
            <table class="widefat">
                <tbody>
                    <?php foreach ($directory_status as $dir => $accessible): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($dir); ?></th>
                            <td>
                                <?php if ($accessible): ?>
                                    <span class="mt-status-ok"><?php _e('OK', 'mobility-trailblazers'); ?></span>
                                <?php else: ?>
                                    <span class="mt-status-error"><?php _e('Not Accessible', 'mobility-trailblazers'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.mt-diagnostic-container {
    max-width: 1200px;
    margin-top: 20px;
}

.mt-diagnostic-section {
    background: #fff;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.mt-diagnostic-section h2 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.mt-status-ok {
    color: #46b450;
    font-weight: 600;
}

.mt-status-error {
    color: #dc3232;
    font-weight: 600;
}

.widefat th {
    width: 200px;
}
</style> 