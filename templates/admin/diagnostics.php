<?php
/**
 * Admin Diagnostics Page Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_options') && !current_user_can('mt_jury_admin')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
}

// Initialize repositories
$evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
$assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();

// Run diagnostics
$diagnostics = [];

// 1. Plugin Information
$diagnostics['plugin'] = [
    'version' => MT_VERSION,
    'db_version' => get_option('mt_db_version', 'Not set'),
    'plugin_dir' => MT_PLUGIN_DIR,
    'plugin_url' => MT_PLUGIN_URL,
    'text_domain' => 'mobility-trailblazers'
];

// 2. System Information
$diagnostics['system'] = [
    'php_version' => PHP_VERSION,
    'wordpress_version' => get_bloginfo('version'),
    'site_url' => get_site_url(),
    'home_url' => get_home_url(),
    'multisite' => is_multisite() ? __('Yes', 'mobility-trailblazers') : __('No', 'mobility-trailblazers'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

// 3. Database Tables
global $wpdb;
$diagnostics['database'] = [
    'prefix' => $wpdb->prefix,
    'charset' => $wpdb->charset,
    'collate' => $wpdb->collate,
    'tables' => []
];

// Check custom tables
$custom_tables = [
    'mt_evaluations' => $wpdb->prefix . 'mt_evaluations',
    'mt_jury_assignments' => $wpdb->prefix . 'mt_jury_assignments'
];

foreach ($custom_tables as $name => $table) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
    $row_count = $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$table}") : 0;
    
    $diagnostics['database']['tables'][$name] = [
        'table_name' => $table,
        'exists' => $table_exists,
        'row_count' => $row_count
    ];
}

// 4. Post Types and Taxonomies
$diagnostics['content'] = [
    'post_types' => [],
    'taxonomies' => []
];

// Check custom post types
$post_types = ['mt_candidate', 'mt_jury_member'];
foreach ($post_types as $post_type) {
    $count = wp_count_posts($post_type);
    $diagnostics['content']['post_types'][$post_type] = [
        'registered' => post_type_exists($post_type),
        'published' => $count->publish ?? 0,
        'draft' => $count->draft ?? 0,
        'total' => array_sum((array)$count)
    ];
}

// Check taxonomies
$taxonomies = ['mt_award_category'];
foreach ($taxonomies as $taxonomy) {
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    $diagnostics['content']['taxonomies'][$taxonomy] = [
        'registered' => taxonomy_exists($taxonomy),
        'term_count' => !is_wp_error($terms) ? count($terms) : 0
    ];
}

// 5. User Roles and Capabilities
$diagnostics['users'] = [
    'roles' => [],
    'capabilities' => []
];

// Check custom role
$jury_role = get_role('mt_jury_member');
$diagnostics['users']['roles']['mt_jury_member'] = [
    'exists' => !is_null($jury_role),
    'capabilities' => $jury_role ? array_keys($jury_role->capabilities) : []
];

// Count users by role
$user_counts = count_users();
$diagnostics['users']['user_counts'] = $user_counts['avail_roles'];

// Check current user capabilities
$current_user = wp_get_current_user();
$mt_capabilities = [
    'mt_manage_evaluations',
    'mt_submit_evaluations',
    'mt_view_all_evaluations',
    'mt_manage_assignments',
    'mt_manage_settings',
    'mt_export_data',
    'mt_import_data',
    'mt_jury_admin'
];

foreach ($mt_capabilities as $cap) {
    $diagnostics['users']['capabilities'][$cap] = current_user_can($cap);
}

// 6. Plugin Settings
$diagnostics['settings'] = [
    'criteria_weights' => get_option('mt_criteria_weights', []),
    
    'evaluations_per_page' => get_option('mt_evaluations_per_page', 10)
];

// 7. AJAX Endpoints
$diagnostics['ajax'] = [
    'ajax_url' => admin_url('admin-ajax.php'),
    'endpoints' => [
        'mt_submit_evaluation' => 'Evaluation submission',
        'mt_save_draft' => 'Save evaluation draft',
        'mt_get_candidate_details' => 'Get candidate details',
        'mt_create_assignment' => 'Create assignment',
        'mt_delete_assignment' => 'Delete assignment',
        'mt_clear_data' => 'Clear data',
        'mt_export_candidates' => 'Export candidates',
        'mt_export_evaluations' => 'Export evaluations'
    ]
];

// 8. Recent Activity
$diagnostics['activity'] = [
    'recent_evaluations' => $evaluation_repo->find_all(['limit' => 5, 'orderby' => 'created_at', 'order' => 'DESC']),
    'recent_assignments' => $assignment_repo->find_all(['limit' => 5, 'orderby' => 'id', 'order' => 'DESC'])
];

// 9. Statistics
$diagnostics['statistics'] = [
    'evaluations' => $evaluation_repo->get_statistics(),
    'assignments' => $assignment_repo->get_statistics()
];

// 10. Error Logs (last 10 entries)
$error_log_file = WP_CONTENT_DIR . '/debug.log';
$diagnostics['errors'] = [
    'debug_enabled' => defined('WP_DEBUG') && WP_DEBUG,
    'debug_log_enabled' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
    'debug_display_enabled' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY,
    'error_log_exists' => file_exists($error_log_file),
    'recent_errors' => []
];

if ($diagnostics['errors']['error_log_exists'] && is_readable($error_log_file)) {
    $log_content = file_get_contents($error_log_file);
    $lines = explode("\n", $log_content);
    $mt_errors = array_filter($lines, function($line) {
        return strpos($line, 'mobility-trailblazers') !== false || strpos($line, 'mt_') !== false;
    });
    $diagnostics['errors']['recent_errors'] = array_slice($mt_errors, -10);
}

// Handle test actions
$test_result = '';
if (isset($_POST['test_action']) && wp_verify_nonce($_POST['_wpnonce'], 'mt_diagnostics')) {
    $test_action = sanitize_text_field($_POST['test_action']);
    
    switch ($test_action) {
        case 'test_database':
            // Test database operations
            $test_data = [
                'jury_member_id' => 1,
                'candidate_id' => 1,
                'courage_score' => 8,
                'innovation_score' => 7,
                'implementation_score' => 9,
                'relevance_score' => 8,
                'visibility_score' => 7,
                'comments' => 'Test evaluation from diagnostics',
                'status' => 'draft'
            ];
            
            $eval_id = $evaluation_repo->create($test_data);
            if ($eval_id) {
                $retrieved = $evaluation_repo->find($eval_id);
                $deleted = $evaluation_repo->delete($eval_id);
                $test_result = sprintf(
                    __('Database test successful! Created evaluation ID: %d, Retrieved: %s, Deleted: %s', 'mobility-trailblazers'),
                    $eval_id,
                    $retrieved ? __('Yes', 'mobility-trailblazers') : __('No', 'mobility-trailblazers'),
                    $deleted ? __('Yes', 'mobility-trailblazers') : __('No', 'mobility-trailblazers')
                );
            } else {
                $test_result = __('Database test failed! Could not create test evaluation.', 'mobility-trailblazers');
            }
            break;
            
        case 'test_ajax':
            // Test AJAX endpoint
            $test_result = __('AJAX test: Check browser console for results.', 'mobility-trailblazers');
            ?>
            <script>
            jQuery(document).ready(function($) {
                $.post(ajaxurl, {
                    action: 'mt_get_dashboard_stats',
                    nonce: '<?php echo wp_create_nonce('mt_admin_nonce'); ?>'
                }, function(response) {
                    console.log('AJAX Test Response:', response);
                    alert(response.success ? 'AJAX test successful!' : 'AJAX test failed!');
                });
            });
            </script>
            <?php
            break;
            
        case 'test_permissions':
            // Test permissions
            $test_user = wp_get_current_user();
            $test_caps = [];
            foreach ($mt_capabilities as $cap) {
                $test_caps[$cap] = user_can($test_user, $cap) ? __('Yes', 'mobility-trailblazers') : __('No', 'mobility-trailblazers');
            }
            $test_result = __('Permissions test completed. See capabilities table below.', 'mobility-trailblazers');
            break;
            
        case 'clear_assignments':
            // Clear all assignments
            if (\MobilityTrailblazers\Core\MT_Database_Upgrade::clear_assignments()) {
                $test_result = __('All assignments have been cleared successfully!', 'mobility-trailblazers');
            } else {
                $test_result = __('Failed to clear assignments.', 'mobility-trailblazers');
            }
            break;
            
        case 'run_db_upgrade':
            // Run database upgrade
            \MobilityTrailblazers\Core\MT_Database_Upgrade::run();
            $test_result = __('Database upgrade completed!', 'mobility-trailblazers');
            break;
            
        case 'force_db_upgrade':
            // Force database upgrade
            \MobilityTrailblazers\Core\MT_Database_Upgrade::force_upgrade();
            $test_result = __('Database upgrade forced and completed!', 'mobility-trailblazers');
            break;
    }
}
?>

<div class="wrap">
    <h1><?php _e('Mobility Trailblazers Diagnostics', 'mobility-trailblazers'); ?></h1>
    
    <?php if ($test_result) : ?>
        <div class="notice notice-info">
            <p><?php echo esc_html($test_result); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Quick Tests -->
    <div class="card">
        <h2><?php _e('Quick Tests', 'mobility-trailblazers'); ?></h2>
        <form method="post" style="display: inline-block; margin-right: 10px;">
            <?php wp_nonce_field('mt_diagnostics'); ?>
            <input type="hidden" name="test_action" value="test_database">
            <button type="submit" class="button button-secondary"><?php _e('Test Database', 'mobility-trailblazers'); ?></button>
        </form>
        
        <form method="post" style="display: inline-block; margin-right: 10px;">
            <?php wp_nonce_field('mt_diagnostics'); ?>
            <input type="hidden" name="test_action" value="test_ajax">
            <button type="submit" class="button button-secondary"><?php _e('Test AJAX', 'mobility-trailblazers'); ?></button>
        </form>
        
        <form method="post" style="display: inline-block;">
            <?php wp_nonce_field('mt_diagnostics'); ?>
            <input type="hidden" name="test_action" value="test_permissions">
            <button type="submit" class="button button-secondary"><?php _e('Test Permissions', 'mobility-trailblazers'); ?></button>
        </form>
    </div>
    
    <!-- Database Operations -->
    <div class="card">
        <h2><?php _e('Database Operations', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Use these operations to fix database issues or reset data.', 'mobility-trailblazers'); ?></p>
        
        <form method="post" style="display: inline-block; margin-right: 10px;">
            <?php wp_nonce_field('mt_diagnostics'); ?>
            <input type="hidden" name="test_action" value="run_db_upgrade">
            <button type="submit" class="button button-primary"><?php _e('Run Database Upgrade', 'mobility-trailblazers'); ?></button>
        </form>
        
        <form method="post" style="display: inline-block; margin-right: 10px;">
            <?php wp_nonce_field('mt_diagnostics'); ?>
            <input type="hidden" name="test_action" value="force_db_upgrade">
            <button type="submit" class="button button-primary" style="background: #d63638; border-color: #d63638;"><?php _e('Force Database Upgrade', 'mobility-trailblazers'); ?></button>
        </form>
        
        <form method="post" style="display: inline-block; margin-right: 10px;" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to clear all assignments? This cannot be undone!', 'mobility-trailblazers'); ?>');">
            <?php wp_nonce_field('mt_diagnostics'); ?>
            <input type="hidden" name="test_action" value="clear_assignments">
            <button type="submit" class="button button-secondary" style="color: #d63638;"><?php _e('Clear All Assignments', 'mobility-trailblazers'); ?></button>
        </form>
        
        <p class="description"><?php _e('Note: Clear Assignments will remove all jury-candidate assignments. Run Database Upgrade if you encounter column errors.', 'mobility-trailblazers'); ?></p>
    </div>
    
    <!-- Plugin Information -->
    <div class="card">
        <h2><?php _e('Plugin Information', 'mobility-trailblazers'); ?></h2>
        <table class="widefat">
            <tbody>
                <?php foreach ($diagnostics['plugin'] as $key => $value) : ?>
                    <tr>
                        <th><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?></th>
                        <td><?php echo esc_html($value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- System Information -->
    <div class="card">
        <h2><?php _e('System Information', 'mobility-trailblazers'); ?></h2>
        <table class="widefat">
            <tbody>
                <?php foreach ($diagnostics['system'] as $key => $value) : ?>
                    <tr>
                        <th><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?></th>
                        <td><?php echo esc_html($value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Database Tables -->
    <div class="card">
        <h2><?php _e('Database Tables', 'mobility-trailblazers'); ?></h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Table', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Name', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Exists', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Row Count', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostics['database']['tables'] as $name => $info) : ?>
                    <tr>
                        <td><?php echo esc_html($name); ?></td>
                        <td><code><?php echo esc_html($info['table_name']); ?></code></td>
                        <td>
                            <span style="color: <?php echo esc_attr($info['exists'] ? 'green' : 'red'); ?>;">
                                <?php echo esc_html($info['exists'] ? '✓' : '✗'); ?>
                            </span>
                        </td>
                        <td><?php echo intval($info['row_count']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Content Types -->
    <div class="card">
        <h2><?php _e('Content Types', 'mobility-trailblazers'); ?></h2>
        
        <h3><?php _e('Post Types', 'mobility-trailblazers'); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Post Type', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Registered', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Published', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Draft', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Total', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostics['content']['post_types'] as $post_type => $info) : ?>
                    <tr>
                        <td><code><?php echo esc_html($post_type); ?></code></td>
                        <td>
                            <span style="color: <?php echo esc_attr($info['registered'] ? 'green' : 'red'); ?>;">
                                <?php echo esc_html($info['registered'] ? '✓' : '✗'); ?>
                            </span>
                        </td>
                        <td><?php echo intval($info['published']); ?></td>
                        <td><?php echo intval($info['draft']); ?></td>
                        <td><?php echo intval($info['total']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3><?php _e('Taxonomies', 'mobility-trailblazers'); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Taxonomy', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Registered', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Term Count', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostics['content']['taxonomies'] as $taxonomy => $info) : ?>
                    <tr>
                        <td><code><?php echo esc_html($taxonomy); ?></code></td>
                        <td>
                            <span style="color: <?php echo esc_attr($info['registered'] ? 'green' : 'red'); ?>;">
                                <?php echo esc_html($info['registered'] ? '✓' : '✗'); ?>
                            </span>
                        </td>
                        <td><?php echo intval($info['term_count']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Users and Capabilities -->
    <div class="card">
        <h2><?php _e('Users and Capabilities', 'mobility-trailblazers'); ?></h2>
        
        <h3><?php _e('User Counts by Role', 'mobility-trailblazers'); ?></h3>
        <table class="widefat">
            <tbody>
                <?php foreach ($diagnostics['users']['user_counts'] as $role => $count) : ?>
                    <tr>
                        <th><?php echo esc_html($role); ?></th>
                        <td><?php echo intval($count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3><?php _e('Current User Capabilities', 'mobility-trailblazers'); ?></h3>
        <table class="widefat">
            <tbody>
                <?php foreach ($diagnostics['users']['capabilities'] as $cap => $has_cap) : ?>
                    <tr>
                        <th><code><?php echo esc_html($cap); ?></code></th>
                        <td>
                            <span style="color: <?php echo esc_attr($has_cap ? 'green' : 'red'); ?>;">
                                <?php echo esc_html($has_cap ? '✓' : '✗'); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Plugin Settings -->
    <div class="card">
        <h2><?php _e('Plugin Settings', 'mobility-trailblazers'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <th><?php _e('Criteria Weights', 'mobility-trailblazers'); ?></th>
                    <td>
                        <?php 
                        if (!empty($diagnostics['settings']['criteria_weights'])) {
                            foreach ($diagnostics['settings']['criteria_weights'] as $criterion => $weight) {
                                echo esc_html($criterion) . ': ' . esc_html($weight) . '<br>';
                            }
                        } else {
                            echo __('Not set', 'mobility-trailblazers');
                        }
                        ?>
                    </td>
                </tr>
                
            </tbody>
        </table>
    </div>
    
    <!-- AJAX Endpoints -->
    <div class="card">
        <h2><?php _e('AJAX Endpoints', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('AJAX URL:', 'mobility-trailblazers'); ?> <code><?php echo esc_html($diagnostics['ajax']['ajax_url']); ?></code></p>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Action', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Description', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostics['ajax']['endpoints'] as $action => $description) : ?>
                    <tr>
                        <td><code><?php echo esc_html($action); ?></code></td>
                        <td><?php echo esc_html($description); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Error Logs -->
    <div class="card">
        <h2><?php _e('Error Logs', 'mobility-trailblazers'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <th><?php _e('Debug Mode', 'mobility-trailblazers'); ?></th>
                    <td>
                        <span style="color: <?php echo esc_attr($diagnostics['errors']['debug_enabled'] ? 'green' : 'red'); ?>;">
                            <?php echo esc_html($diagnostics['errors']['debug_enabled'] ? __('Enabled', 'mobility-trailblazers') : __('Disabled', 'mobility-trailblazers')); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Debug Log', 'mobility-trailblazers'); ?></th>
                    <td>
                        <span style="color: <?php echo esc_attr($diagnostics['errors']['debug_log_enabled'] ? 'green' : 'red'); ?>;">
                            <?php echo esc_html($diagnostics['errors']['debug_log_enabled'] ? __('Enabled', 'mobility-trailblazers') : __('Disabled', 'mobility-trailblazers')); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Error Log File', 'mobility-trailblazers'); ?></th>
                    <td>
                        <span style="color: <?php echo esc_attr($diagnostics['errors']['error_log_exists'] ? 'green' : 'red'); ?>;">
                            <?php echo esc_html($diagnostics['errors']['error_log_exists'] ? __('Exists', 'mobility-trailblazers') : __('Not Found', 'mobility-trailblazers')); ?>
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php if (!empty($diagnostics['errors']['recent_errors'])) : ?>
            <h3><?php _e('Recent Plugin Errors', 'mobility-trailblazers'); ?></h3>
            <div style="background: #f1f1f1; padding: 10px; overflow-x: auto;">
                <pre style="margin: 0; font-size: 12px;"><?php 
                    foreach ($diagnostics['errors']['recent_errors'] as $error) {
                        echo esc_html($error) . "\n";
                    }
                ?></pre>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Export Diagnostics -->
    <div class="card">
        <h2><?php _e('Export Diagnostics', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Copy the diagnostic data below to share with support:', 'mobility-trailblazers'); ?></p>
        <textarea id="diagnostic-export" rows="10" style="width: 100%; font-family: monospace; font-size: 12px;" readonly><?php
            echo "=== Mobility Trailblazers Diagnostics ===\n";
            echo "Generated: " . current_time('mysql') . "\n\n";
            
            // Plugin Info
            echo "Plugin Version: " . MT_VERSION . "\n";
            echo "DB Version: " . get_option('mt_db_version', 'Not set') . "\n\n";
            
            // System Info
            echo "PHP Version: " . PHP_VERSION . "\n";
            echo "WordPress Version: " . get_bloginfo('version') . "\n";
            echo "Site URL: " . get_site_url() . "\n\n";
            
            // Database
            echo "Database Tables:\n";
            foreach ($diagnostics['database']['tables'] as $name => $info) {
                echo "- {$name}: " . ($info['exists'] ? "OK ({$info['row_count']} rows)" : "MISSING") . "\n";
            }
            echo "\n";
            
            // Content
            echo "Content:\n";
            foreach ($diagnostics['content']['post_types'] as $post_type => $info) {
                echo "- {$post_type}: " . ($info['registered'] ? "OK ({$info['total']} total)" : "NOT REGISTERED") . "\n";
            }
            echo "\n";
            
            // Statistics
            echo "Statistics:\n";
            echo "- Total Evaluations: " . $diagnostics['statistics']['evaluations']['total'] . "\n";
            echo "- Completed Evaluations: " . $diagnostics['statistics']['evaluations']['completed'] . "\n";
            echo "- Total Assignments: " . $diagnostics['statistics']['assignments']['total_assignments'] . "\n";
        ?></textarea>
        <p>
            <button type="button" class="button button-primary" onclick="document.getElementById('diagnostic-export').select(); document.execCommand('copy'); alert('<?php esc_attr_e('Diagnostic data copied to clipboard!', 'mobility-trailblazers'); ?>');">
                <?php _e('Copy to Clipboard', 'mobility-trailblazers'); ?>
            </button>
        </p>
    </div>
</div>

<style>
.card {
    max-width: none;
    margin-bottom: 20px;
}
.card h3 {
    margin-top: 20px;
}
.widefat th {
    width: 30%;
}
.widefat code {
    font-size: 12px;
    background: #f0f0f0;
    padding: 2px 4px;
}
</style> 