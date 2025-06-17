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

if (!function_exists('mt_get_all_jury_members')):
/**
 * Get all jury members
 * 
 * @param array $args Additional query arguments
 * @return array Array of jury member posts
 */
function mt_get_all_jury_members($args = array()) {
    $defaults = array(
        'post_type' => mt_get_jury_post_type(),
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'any'
    );
    
    $args = wp_parse_args($args, $defaults);
    return get_posts($args);
}
endif;

if (!function_exists('mt_get_unlinked_jury_members')):
/**
 * Get unlinked jury members (no user account)
 * 
 * @return array Array of jury member posts without linked users
 */
function mt_get_unlinked_jury_members() {
    return mt_get_all_jury_members(array(
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_mt_user_id',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => '_mt_user_id',
                'value' => '',
                'compare' => '='
            ),
            array(
                'key' => '_mt_user_id',
                'value' => '0',
                'compare' => '='
            ),
            array(
                'key' => '_mt_user_id',
                'value' => 'false',
                'compare' => '='
            ),
            array(
                'key' => '_mt_user_id',
                'value' => 'null',
                'compare' => '='
            )
        )
    ));
}
endif;

if (!function_exists('mt_get_linked_jury_members')):
/**
 * Get linked jury members (have user account)
 * 
 * @return array Array of jury member posts with linked users
 */
function mt_get_linked_jury_members() {
    return mt_get_all_jury_members(array(
        'meta_query' => array(
            array(
                'key' => '_mt_user_id',
                'compare' => 'EXISTS',
                'value' => '',
                'compare' => '!='
            ),
            array(
                'key' => '_mt_user_id',
                'value' => array('', '0', 'false', 'null'),
                'compare' => 'NOT IN'
            )
        )
    ));
}
endif;

if (!function_exists('mt_jury_has_user')):
/**
 * Check if a jury member is linked to a user
 * 
 * @param int $jury_id The jury member post ID
 * @return bool|int False if not linked, user ID if linked
 */
function mt_jury_has_user($jury_id) {
    $user_id = get_post_meta($jury_id, '_mt_user_id', true);
    
    // Check for various "empty" values
    if (empty($user_id) || $user_id === '0' || $user_id === 'false' || $user_id === 'null') {
        return false;
    }
    
    // Verify the user exists
    $user = get_user_by('id', $user_id);
    return $user ? $user_id : false;
}
endif;

// Get all jury members for the current view
$jury_members = mt_get_all_jury_members();

// Get all users who could be jury members
$potential_users = get_users(array(
    'orderby' => 'display_name',
    'order' => 'ASC'
));

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
        <?php
        // Check if there are capability issues
        $has_capability_issues = false;
        if (isset($results['roles'])) {
            foreach ($results['roles'] as $check) {
                if ($check['name'] === __('Admin Capabilities', 'mobility-trailblazers') && $check['status'] !== 'success') {
                    $has_capability_issues = true;
                    break;
                }
            }
        }
        if ($has_capability_issues && current_user_can('manage_options')):
        ?>
            <a href="<?php echo admin_url('admin.php?page=mt-fix-capabilities'); ?>" class="button button-secondary">
                <?php _e('Fix Capabilities', 'mobility-trailblazers'); ?>
            </a>
        <?php endif; ?>
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
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $section => $checks): ?>
                <div class="mt-diagnostic-group">
                    <h3><?php echo esc_html($section); ?></h3>
                    <table class="widefat">
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
                                        <span class="mt-status status-<?php echo esc_attr($check['status']); ?>">
                                            <?php echo esc_html($check['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($check['message']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p><?php _e('No diagnostic results available.', 'mobility-trailblazers'); ?></p>
        <?php endif; ?>
    </div>
</div> 