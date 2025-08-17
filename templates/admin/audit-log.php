<?php
/**
 * Audit Log Admin Template
 *
 * @package MobilityTrailblazers
 * @since 2.2.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$current_user_filter = isset($_GET['user_id']) ? absint($_GET['user_id']) : '';
$current_action_filter = isset($_GET['action_filter']) ? sanitize_text_field($_GET['action_filter']) : '';
$current_object_type_filter = isset($_GET['object_type']) ? sanitize_text_field($_GET['object_type']) : '';
$current_date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$current_date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$current_per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 20;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" id="audit-log-filter">
                <input type="hidden" name="page" value="mt-audit-log" />
                
                <!-- User Filter -->
                <select name="user_id" id="filter-by-user">
                    <option value=""><?php _e('All Users', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($current_user_filter, $user->ID); ?>>
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Action Filter -->
                <select name="action_filter" id="filter-by-action">
                    <option value=""><?php _e('All Actions', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($unique_actions as $action): ?>
                        <option value="<?php echo esc_attr($action); ?>" <?php selected($current_action_filter, $action); ?>>
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $action))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Object Type Filter -->
                <select name="object_type" id="filter-by-object-type">
                    <option value=""><?php _e('All Object Types', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($unique_object_types as $object_type): ?>
                        <option value="<?php echo esc_attr($object_type); ?>" <?php selected($current_object_type_filter, $object_type); ?>>
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $object_type))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Date Filters -->
                <label for="date-from"><?php _e('From:', 'mobility-trailblazers'); ?></label>
                <input type="date" name="date_from" id="date-from" value="<?php echo esc_attr($current_date_from); ?>" />
                
                <label for="date-to"><?php _e('To:', 'mobility-trailblazers'); ?></label>
                <input type="date" name="date_to" id="date-to" value="<?php echo esc_attr($current_date_to); ?>" />
                
                <!-- Per Page -->
                <select name="per_page" id="per-page">
                    <option value="10" <?php selected($current_per_page, 10); ?>>10</option>
                    <option value="20" <?php selected($current_per_page, 20); ?>>20</option>
                    <option value="50" <?php selected($current_per_page, 50); ?>>50</option>
                    <option value="100" <?php selected($current_per_page, 100); ?>>100</option>
                </select>
                
                <?php submit_button(__('Filter', 'mobility-trailblazers'), 'action', 'filter_action', false, ['id' => 'audit-filter-submit']); ?>
                
                <?php if (array_filter([$current_user_filter, $current_action_filter, $current_object_type_filter, $current_date_from, $current_date_to])): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=mt-audit-log')); ?>" class="button">
                        <?php _e('Clear Filters', 'mobility-trailblazers'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Results count -->
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(
                    _n('%s item', '%s items', $logs_data['total_items'], 'mobility-trailblazers'),
                    number_format_i18n($logs_data['total_items'])
                ); ?>
            </span>
            
            <?php if ($logs_data['total_pages'] > 1): ?>
                <?php
                $pagination_args = array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $logs_data['total_pages'],
                    'current' => $logs_data['current_page']
                );
                
                $page_links = paginate_links($pagination_args);
                if ($page_links) {
                    echo '<span class="pagination-links">' . $page_links . '</span>';
                }
                ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Audit Log Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-user">
                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'user_id', 'order' => ($_GET['orderby'] ?? '') === 'user_id' && ($_GET['order'] ?? '') === 'ASC' ? 'DESC' : 'ASC'))); ?>">
                        <?php _e('User', 'mobility-trailblazers'); ?>
                        <?php if (($_GET['orderby'] ?? '') === 'user_id'): ?>
                            <span class="sorting-indicator"></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th scope="col" class="manage-column column-action">
                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'action', 'order' => ($_GET['orderby'] ?? '') === 'action' && ($_GET['order'] ?? '') === 'ASC' ? 'DESC' : 'ASC'))); ?>">
                        <?php _e('Action', 'mobility-trailblazers'); ?>
                        <?php if (($_GET['orderby'] ?? '') === 'action'): ?>
                            <span class="sorting-indicator"></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th scope="col" class="manage-column column-object-type">
                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'object_type', 'order' => ($_GET['orderby'] ?? '') === 'object_type' && ($_GET['order'] ?? '') === 'ASC' ? 'DESC' : 'ASC'))); ?>">
                        <?php _e('Type', 'mobility-trailblazers'); ?>
                        <?php if (($_GET['orderby'] ?? '') === 'object_type'): ?>
                            <span class="sorting-indicator"></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th scope="col" class="manage-column column-object-id">
                    <?php _e('Object', 'mobility-trailblazers'); ?>
                </th>
                <th scope="col" class="manage-column column-details">
                    <?php _e('Details', 'mobility-trailblazers'); ?>
                </th>
                <th scope="col" class="manage-column column-date">
                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'created_at', 'order' => ($_GET['orderby'] ?? '') === 'created_at' && ($_GET['order'] ?? '') === 'ASC' ? 'DESC' : 'ASC'))); ?>">
                        <?php _e('Date', 'mobility-trailblazers'); ?>
                        <?php if (($_GET['orderby'] ?? 'created_at') === 'created_at'): ?>
                            <span class="sorting-indicator"></span>
                        <?php endif; ?>
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs_data['items'])): ?>
                <?php foreach ($logs_data['items'] as $log): ?>
                    <tr>
                        <td class="column-user">
                            <?php if ($log->user_name): ?>
                                <strong><?php echo esc_html($log->user_name); ?></strong>
                                <br><small class="description"><?php echo esc_html($log->user_email); ?></small>
                            <?php else: ?>
                                <em><?php _e('System', 'mobility-trailblazers'); ?></em>
                                <br><small class="description">ID: <?php echo esc_html($log->user_id); ?></small>
                            <?php endif; ?>
                        </td>
                        
                        <td class="column-action">
                            <code><?php echo esc_html($log->action); ?></code>
                        </td>
                        
                        <td class="column-object-type">
                            <span class="object-type-badge object-type-<?php echo esc_attr($log->object_type); ?>">
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $log->object_type))); ?>
                            </span>
                        </td>
                        
                        <td class="column-object-id">
                            <?php
                            // Try to get object title/name
                            $object_title = '';
                            if ($log->object_type === 'candidate' || $log->object_type === 'jury_member') {
                                $post = get_post($log->object_id);
                                $object_title = $post ? $post->post_title : '';
                            }
                            ?>
                            <?php if ($object_title): ?>
                                <strong><?php echo esc_html($object_title); ?></strong>
                                <br><small class="description">ID: <?php echo esc_html($log->object_id); ?></small>
                            <?php else: ?>
                                ID: <?php echo esc_html($log->object_id); ?>
                            <?php endif; ?>
                        </td>
                        
                        <td class="column-details">
                            <?php if ($log->details): ?>
                                <?php
                                $details = json_decode($log->details, true);
                                if (is_array($details) && !empty($details)):
                                ?>
                                    <details>
                                        <summary><?php _e('View Details', 'mobility-trailblazers'); ?></summary>
                                        <pre style="white-space: pre-wrap; font-size: 11px; background: #f9f9f9; padding: 5px; margin-top: 5px; max-height: 150px; overflow-y: auto;"><?php echo esc_html(json_encode($details, JSON_PRETTY_PRINT)); ?></pre>
                                    </details>
                                <?php else: ?>
                                    <span class="description"><?php _e('No additional details', 'mobility-trailblazers'); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="description">—</span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="column-date">
                            <?php
                            $date = mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $log->created_at);
                            echo esc_html($date);
                            ?>
                            <br><small class="description">
                                <?php echo esc_html(human_time_diff(strtotime($log->created_at), current_time('timestamp'))) . ' ' . __('ago', 'mobility-trailblazers'); ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="no-items">
                        <?php _e('No audit log entries found.', 'mobility-trailblazers'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Bottom pagination -->
    <?php if ($logs_data['total_pages'] > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php echo '<span class="pagination-links">' . $page_links . '</span>'; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* Additional styling for audit log */
.object-type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.object-type-candidate {
    background-color: #e3f2fd;
    color: #1565c0;
}

.object-type-evaluation {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.object-type-assignment {
    background-color: #e8f5e8;
    color: #2e7d32;
}

.object-type-jury_member {
    background-color: #fff3e0;
    color: #f57c00;
}

.column-details details summary {
    cursor: pointer;
    color: #0073aa;
}

.column-details details summary:hover {
    color: #005177;
}

#audit-log-filter {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

#audit-log-filter select,
#audit-log-filter input[type="date"] {
    margin-right: 5px;
}

#audit-log-filter label {
    font-weight: 600;
    margin-left: 10px;
}

.wp-list-table .column-user {
    width: 15%;
}

.wp-list-table .column-action {
    width: 12%;
}

.wp-list-table .column-object-type {
    width: 10%;
}

.wp-list-table .column-object-id {
    width: 15%;
}

.wp-list-table .column-details {
    width: 30%;
}

.wp-list-table .column-date {
    width: 18%;
}
</style>