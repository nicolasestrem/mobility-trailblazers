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

/**
 * Get the jury member post type name
 * 
 * @return string The post type name for jury members
 */

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
        
        <?php foreach ($results as $category => $checks): ?>
            <div class="mt-diagnostic-category">
                <h3><?php echo esc_html(ucfirst(str_replace('_', ' ', $category))); ?></h3>
                <table class="widefat mt-diagnostic-table">
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
                                    <?php if ($check['status'] === 'success'): ?>
                                        <span class="mt-status-pass">✓ <?php _e('PASS', 'mobility-trailblazers'); ?></span>
                                    <?php elseif ($check['status'] === 'warning'): ?>
                                        <span class="mt-status-warning">⚠ <?php _e('WARNING', 'mobility-trailblazers'); ?></span>
                                    <?php elseif ($check['status'] === 'info'): ?>
                                        <span class="mt-status-info">ℹ <?php _e('INFO', 'mobility-trailblazers'); ?></span>
                                    <?php else: ?>
                                        <span class="mt-status-fail">✗ <?php _e('FAIL', 'mobility-trailblazers'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($check['message']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Database Statistics -->
    <div class="mt-diagnostic-section">
        <h2><?php _e('Database Statistics', 'mobility-trailblazers'); ?></h2>
        <?php
        global $wpdb;
        $stats = array(
            'candidates' => $wpdb->get_var("
                SELECT COUNT(DISTINCT p.ID) 
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'mt_candidate' 
                AND p.post_status = 'publish'
                AND pm.meta_key = '_mt_assigned_jury_members' 
                AND pm.meta_value != ''
                AND pm.meta_value != 'a:0:{}'
                AND pm.meta_value IS NOT NULL
            "),
            'jury_members' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_jury' AND post_status = 'publish'"),
            'votes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE is_active = 1"),
            'evaluations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores"),
            'backups' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_backup'"),
            'reset_logs' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vote_reset_logs")
        );
        ?>
        <table class="widefat">
            <tbody>
                <tr>
                    <th><?php _e('Total Candidates', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['candidates']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Total Jury Members', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['jury_members']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Active Votes', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['votes']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Total Evaluations', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['evaluations']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Backup Records', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['backups']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Reset Log Entries', 'mobility-trailblazers'); ?></th>
                    <td><?php echo number_format($stats['reset_logs']); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Recent Activity -->
    <div class="mt-diagnostic-section">
        <h2><?php _e('Recent Activity', 'mobility-trailblazers'); ?></h2>
        <?php
        $recent_votes = $wpdb->get_results("
            SELECT v.*, c.post_title as candidate_name, j.post_title as jury_name
            FROM {$wpdb->prefix}mt_votes v
            LEFT JOIN {$wpdb->posts} c ON v.candidate_id = c.ID
            LEFT JOIN {$wpdb->posts} j ON v.jury_member_id = j.ID
            WHERE v.is_active = 1
            ORDER BY v.created_at DESC
            LIMIT 10
        ");
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Time', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Score', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_votes)): ?>
                    <tr>
                        <td colspan="4"><?php _e('No recent activity', 'mobility-trailblazers'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_votes as $vote): ?>
                        <tr>
                            <td><?php echo human_time_diff(strtotime($vote->created_at), current_time('timestamp')) . ' ' . __('ago', 'mobility-trailblazers'); ?></td>
                            <td><?php echo esc_html($vote->jury_name); ?></td>
                            <td><?php echo esc_html($vote->candidate_name); ?></td>
                            <td><?php echo esc_html($vote->total_score); ?>/50</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Bulk Jury Member User Account Creation Tool -->
    <div class="mt-diagnostic-section">
        <h2><?php _e('Bulk Jury Member User Account Creation', 'mobility-trailblazers'); ?></h2>
        <?php
        /**
         * Bulk Jury Member User Account Creation Tool
         * Add or update this to diagnostic.php 
         */

        // Security check
        if (!defined('ABSPATH')) {
            exit;
        }

        // Handle bulk creation form submission
        if (isset($_POST['mt_bulk_create_users']) && wp_verify_nonce($_POST['mt_bulk_create_nonce'], 'mt_bulk_create_users')) {
            $selected_jury_ids = isset($_POST['jury_members']) ? array_map('intval', $_POST['jury_members']) : array();
            $username_pattern = sanitize_text_field($_POST['username_pattern']);
            $email_domain = sanitize_text_field($_POST['email_domain']);
            $use_custom_email = isset($_POST['use_custom_email']);
            $send_notifications = isset($_POST['send_notifications']);
            $password_type = sanitize_text_field($_POST['password_type']);
            $custom_password = sanitize_text_field($_POST['custom_password']);
            
            $created_count = 0;
            $errors = array();
            
            foreach ($selected_jury_ids as $jury_id) {
                $jury_member = get_post($jury_id);
                if (!$jury_member) continue;
                
                // Check if already linked
                $existing_user_id = get_post_meta($jury_id, '_mt_user_id', true);
                if ($existing_user_id) {
                    $errors[] = sprintf(__('Jury member "%s" is already linked to a user.', 'mobility-trailblazers'), $jury_member->post_title);
                    continue;
                }
                
                // Generate username based on pattern
                $username = '';
                switch ($username_pattern) {
                    case 'firstname.lastname':
                        $username = strtolower(str_replace(' ', '.', $jury_member->post_title));
                        break;
                    case 'firstname_lastname':
                        $username = strtolower(str_replace(' ', '_', $jury_member->post_title));
                        break;
                    case 'firstnamelastname':
                        $username = strtolower(str_replace(' ', '', $jury_member->post_title));
                        break;
                    case 'jury_id':
                        $username = 'jury_' . $jury_id;
                        break;
                    case 'custom':
                        $custom_pattern = sanitize_text_field($_POST['custom_username_pattern']);
                        $username = str_replace(
                            array('{name}', '{id}', '{date}'),
                            array(
                                strtolower(str_replace(' ', '', $jury_member->post_title)),
                                $jury_id,
                                date('Ymd')
                            ),
                            $custom_pattern
                        );
                        break;
                }
                
                // Ensure username is unique
                $base_username = $username;
                $counter = 1;
                while (username_exists($username)) {
                    $username = $base_username . $counter;
                    $counter++;
                }
                
                // Generate email
                $email = '';
                if ($use_custom_email && $email_domain) {
                    // Use custom email pattern
                    $email = $username . '@' . $email_domain;
                } else {
                    // Use jury member's existing email
                    $email = get_post_meta($jury_id, '_mt_email', true);
                    if (!$email) {
                        // Fallback to generated email
                        $email = $username . '@' . parse_url(home_url(), PHP_URL_HOST);
                    }
                }
                
                // Check if email already exists
                if (email_exists($email)) {
                    $errors[] = sprintf(__('Email %s already exists for jury member "%s".', 'mobility-trailblazers'), $email, $jury_member->post_title);
                    continue;
                }
                
                // Generate password
                $password = '';
                switch ($password_type) {
                    case 'random':
                        $password = wp_generate_password(12, true, false);
                        break;
                    case 'custom':
                        $password = $custom_password;
                        break;
                    case 'pattern':
                        $password_pattern = sanitize_text_field($_POST['password_pattern']);
                        $password = str_replace(
                            array('{username}', '{date}', '{random}'),
                            array(
                                $username,
                                date('Y'),
                                wp_generate_password(4, false, false)
                            ),
                            $password_pattern
                        );
                        break;
                }
                
                // Create user
                $user_data = array(
                    'user_login' => $username,
                    'user_email' => $email,
                    'user_pass' => $password,
                    'display_name' => $jury_member->post_title,
                    'role' => 'mt_jury_member'
                );
                
                $user_id = wp_insert_user($user_data);
                
                if (!is_wp_error($user_id)) {
                    // Link user to jury member
                    update_post_meta($jury_id, '_mt_user_id', $user_id);
                    update_user_meta($user_id, '_mt_jury_member_id', $jury_id);
                    
                    // Update jury member email if using custom
                    if ($use_custom_email) {
                        update_post_meta($jury_id, '_mt_email', $email);
                    }
                    
                    // Store credentials for display
                    $created_users[] = array(
                        'jury_name' => $jury_member->post_title,
                        'username' => $username,
                        'email' => $email,
                        'password' => $password
                    );
                    
                    // Send notification if requested
                    if ($send_notifications) {
                        wp_new_user_notification($user_id, null, 'both');
                    }
                    
                    $created_count++;
                } else {
                    $errors[] = sprintf(__('Failed to create user for "%s": %s', 'mobility-trailblazers'), $jury_member->post_title, $user_id->get_error_message());
                }
            }
            
            // Store results in transient for display
            if ($created_count > 0) {
                set_transient('mt_bulk_created_users', $created_users, 300); // 5 minutes
            }
        }

        // Get unlinked jury members
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'any',
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
                )
            )
        ));

        // Check for recently created users to display
        $created_users = get_transient('mt_bulk_created_users');
        ?>

        <div class="mt-bulk-creation-tool">
            <?php if (isset($created_count) && $created_count > 0) : ?>
                <div class="notice notice-success">
                    <p><?php printf(__('Successfully created %d user account(s).', 'mobility-trailblazers'), $created_count); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)) : ?>
                <div class="notice notice-error">
                    <p><strong><?php _e('Some errors occurred:', 'mobility-trailblazers'); ?></strong></p>
                    <ul>
                        <?php foreach ($errors as $error) : ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($created_users && !isset($_POST['mt_bulk_create_users'])) : ?>
                <div class="mt-created-users-list">
                    <h3><?php _e('Recently Created User Accounts', 'mobility-trailblazers'); ?></h3>
                    <p class="description"><?php _e('Save these credentials - passwords cannot be retrieved later!', 'mobility-trailblazers'); ?></p>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                                <th><?php _e('Username', 'mobility-trailblazers'); ?></th>
                                <th><?php _e('Email', 'mobility-trailblazers'); ?></th>
                                <th><?php _e('Password', 'mobility-trailblazers'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($created_users as $user) : ?>
                            <tr>
                                <td><?php echo esc_html($user['jury_name']); ?></td>
                                <td><code><?php echo esc_html($user['username']); ?></code></td>
                                <td><?php echo esc_html($user['email']); ?></td>
                                <td>
                                    <code class="password-field"><?php echo esc_html($user['password']); ?></code>
                                    <button class="button button-small copy-password" data-password="<?php echo esc_attr($user['password']); ?>">
                                        <?php _e('Copy', 'mobility-trailblazers'); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <p class="mt-export-actions">
                        <button class="button" id="export-credentials-csv"><?php _e('Export as CSV', 'mobility-trailblazers'); ?></button>
                        <button class="button" id="print-credentials"><?php _e('Print', 'mobility-trailblazers'); ?></button>
                    </p>
                </div>
                <?php delete_transient('mt_bulk_created_users'); ?>
            <?php endif; ?>
            
            <?php if (empty($jury_members)) : ?>
                <p><?php _e('All jury members are already linked to user accounts.', 'mobility-trailblazers'); ?></p>
            <?php else : ?>
            
            <form method="post" action="" id="bulk-create-form">
                <?php wp_nonce_field('mt_bulk_create_users', 'mt_bulk_create_nonce'); ?>
                
                <div class="mt-form-section">
                    <h3><?php _e('1. Select Jury Members', 'mobility-trailblazers'); ?></h3>
                    
                    <div class="mt-select-controls">
                        <button type="button" class="button" id="select-all"><?php _e('Select All', 'mobility-trailblazers'); ?></button>
                        <button type="button" class="button" id="select-none"><?php _e('Select None', 'mobility-trailblazers'); ?></button>
                        <span class="selected-count">
                            <span id="selected-count">0</span> <?php _e('selected', 'mobility-trailblazers'); ?>
                        </span>
                    </div>
                    
                    <div class="mt-jury-selection">
                        <?php foreach ($jury_members as $jury) : 
                            $email = get_post_meta($jury->ID, '_mt_email', true);
                        ?>
                        <label class="jury-member-item">
                            <input type="checkbox" name="jury_members[]" value="<?php echo $jury->ID; ?>" class="jury-checkbox">
                            <span class="jury-name"><?php echo esc_html($jury->post_title); ?></span>
                            <?php if ($email) : ?>
                                <span class="jury-email">(<?php echo esc_html($email); ?>)</span>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mt-form-section">
                    <h3><?php _e('2. Username Pattern', 'mobility-trailblazers'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Username Format', 'mobility-trailblazers'); ?></th>
                            <td>
                                <select name="username_pattern" id="username_pattern">
                                    <option value="firstname.lastname"><?php _e('firstname.lastname', 'mobility-trailblazers'); ?></option>
                                    <option value="firstname_lastname"><?php _e('firstname_lastname', 'mobility-trailblazers'); ?></option>
                                    <option value="firstnamelastname"><?php _e('firstnamelastname', 'mobility-trailblazers'); ?></option>
                                    <option value="jury_id"><?php _e('jury_123 (using ID)', 'mobility-trailblazers'); ?></option>
                                    <option value="custom"><?php _e('Custom pattern', 'mobility-trailblazers'); ?></option>
                                </select>
                                
                                <div id="custom-username-section" style="display: none; margin-top: 10px;">
                                    <input type="text" name="custom_username_pattern" class="regular-text" placeholder="{name}_{date}">
                                    <p class="description">
                                        <?php _e('Available variables: {name}, {id}, {date}', 'mobility-trailblazers'); ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="mt-form-section">
                    <h3><?php _e('3. Email Configuration', 'mobility-trailblazers'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Email Source', 'mobility-trailblazers'); ?></th>
                            <td>
                                <label>
                                    <input type="radio" name="use_custom_email" value="0" checked>
                                    <?php _e('Use existing jury member emails', 'mobility-trailblazers'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="use_custom_email" value="1">
                                    <?php _e('Generate emails with custom domain', 'mobility-trailblazers'); ?>
                                </label>
                                
                                <div id="custom-email-section" style="display: none; margin-top: 10px;">
                                    <input type="text" name="email_domain" class="regular-text" placeholder="example.com">
                                    <p class="description">
                                        <?php _e('Emails will be: username@domain', 'mobility-trailblazers'); ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="mt-form-section">
                    <h3><?php _e('4. Password Configuration', 'mobility-trailblazers'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Password Type', 'mobility-trailblazers'); ?></th>
                            <td>
                                <select name="password_type" id="password_type">
                                    <option value="random"><?php _e('Generate random passwords', 'mobility-trailblazers'); ?></option>
                                    <option value="custom"><?php _e('Same password for all', 'mobility-trailblazers'); ?></option>
                                    <option value="pattern"><?php _e('Pattern-based password', 'mobility-trailblazers'); ?></option>
                                </select>
                                
                                <div id="custom-password-section" style="display: none; margin-top: 10px;">
                                    <input type="text" name="custom_password" class="regular-text" placeholder="<?php _e('Enter password', 'mobility-trailblazers'); ?>">
                                    <p class="description"><?php _e('All users will have this password', 'mobility-trailblazers'); ?></p>
                                </div>
                                
                                <div id="pattern-password-section" style="display: none; margin-top: 10px;">
                                    <input type="text" name="password_pattern" class="regular-text" placeholder="Award{date}_{username}">
                                    <p class="description">
                                        <?php _e('Variables: {username}, {date}, {random}', 'mobility-trailblazers'); ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="mt-form-section">
                    <h3><?php _e('5. Notifications', 'mobility-trailblazers'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Email Notifications', 'mobility-trailblazers'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="send_notifications" value="1">
                                    <?php _e('Send new user notification emails', 'mobility-trailblazers'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('WordPress will send login credentials to each new user', 'mobility-trailblazers'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <button type="submit" name="mt_bulk_create_users" class="button button-primary" id="bulk-create-submit" disabled>
                        <?php _e('Create User Accounts', 'mobility-trailblazers'); ?>
                    </button>
                    <span class="spinner"></span>
                </p>
            </form>
            
            <?php endif; ?>
        </div>

        <!-- Jury Member User Account Linking Tool -->
        <div class="mt-diagnostic-section mt-jury-linking-tool">
            <h2><?php _e('Jury Member User Account Linking', 'mobility-trailblazers'); ?></h2>
            
            <div class="mt-tool-description">
                <p><?php _e('This tool allows you to link jury members to WordPress user accounts. You can either link to an existing user or create a new user account.', 'mobility-trailblazers'); ?></p>
            </div>
            
            <!-- Jury Members Overview -->
            <div class="mt-jury-members-list">
                <h3><?php _e('Jury Members Status', 'mobility-trailblazers'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Email', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Linked User', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jury_members as $jury_member) : 
                            $user_id = get_post_meta($jury_member->ID, '_mt_user_id', true);
                            $user = $user_id ? get_user_by('id', $user_id) : null;
                            $email = get_post_meta($jury_member->ID, '_mt_email', true);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($jury_member->post_title); ?></strong>
                                <br>
                                <span class="description">ID: <?php echo $jury_member->ID; ?></span>
                            </td>
                            <td><?php echo $email ? esc_html($email) : '<em>' . __('No email', 'mobility-trailblazers') . '</em>'; ?></td>
                            <td>
                                <?php if ($user) : ?>
                                    <a href="<?php echo get_edit_user_link($user->ID); ?>">
                                        <?php echo esc_html($user->display_name); ?>
                                    </a>
                                    <br>
                                    <span class="description"><?php echo esc_html($user->user_login); ?></span>
                                <?php else : ?>
                                    <em><?php _e('Not linked', 'mobility-trailblazers'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user) : ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php _e('Linked', 'mobility-trailblazers'); ?>
                                <?php else : ?>
                                    <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
                                    <?php _e('Not linked', 'mobility-trailblazers'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user) : ?>
                                    <button class="button button-small mt-unlink-user" data-jury-id="<?php echo $jury_member->ID; ?>">
                                        <?php _e('Unlink', 'mobility-trailblazers'); ?>
                                    </button>
                                <?php else : ?>
                                    <button class="button button-small button-primary mt-link-user" data-jury-id="<?php echo $jury_member->ID; ?>" data-email="<?php echo esc_attr($email); ?>" data-name="<?php echo esc_attr($jury_member->post_title); ?>">
                                        <?php _e('Link User', 'mobility-trailblazers'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Link/Create Form (Hidden by default) -->
            <div id="mt-link-user-form" style="display: none;">
                <h3><?php _e('Link Jury Member to User Account', 'mobility-trailblazers'); ?></h3>
                
                <form method="post" action="">
                    <?php wp_nonce_field('mt_link_jury_user', 'mt_jury_link_nonce'); ?>
                    <input type="hidden" name="jury_member_id" id="link-jury-member-id" value="">
                    
                    <div class="mt-link-options">
                        <label>
                            <input type="radio" name="link_action" value="existing" checked>
                            <?php _e('Link to existing user', 'mobility-trailblazers'); ?>
                        </label>
                        <label>
                            <input type="radio" name="link_action" value="create">
                            <?php _e('Create new user', 'mobility-trailblazers'); ?>
                        </label>
                    </div>
                    
                    <!-- Existing User Selection -->
                    <div id="existing-user-section" class="mt-form-section">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="existing_user_id"><?php _e('Select User', 'mobility-trailblazers'); ?></label>
                                </th>
                                <td>
                                    <select name="existing_user_id" id="existing_user_id" class="regular-text">
                                        <option value=""><?php _e('— Select User —', 'mobility-trailblazers'); ?></option>
                                        <?php foreach ($potential_users as $user) : 
                                            $has_jury = get_user_meta($user->ID, '_mt_jury_member_id', true);
                                        ?>
                                        <option value="<?php echo $user->ID; ?>" <?php echo $has_jury ? 'disabled' : ''; ?>>
                                            <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_login); ?>)
                                            <?php echo $has_jury ? ' - ' . __('Already linked', 'mobility-trailblazers') : ''; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- New User Creation -->
                    <div id="new-user-section" class="mt-form-section" style="display: none;">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="new_user_login"><?php _e('Username', 'mobility-trailblazers'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="new_user_login" id="new_user_login" class="regular-text">
                                    <p class="description"><?php _e('Required. Username for login.', 'mobility-trailblazers'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="new_user_email"><?php _e('Email', 'mobility-trailblazers'); ?></label>
                                </th>
                                <td>
                                    <input type="email" name="new_user_email" id="new_user_email" class="regular-text">
                                    <p class="description"><?php _e('Required. Will be used for notifications.', 'mobility-trailblazers'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="new_user_display_name"><?php _e('Display Name', 'mobility-trailblazers'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="new_user_display_name" id="new_user_display_name" class="regular-text">
                                    <p class="description"><?php _e('Optional. How the name is displayed publicly.', 'mobility-trailblazers'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Notification', 'mobility-trailblazers'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="send_notification" value="1" checked>
                                        <?php _e('Send new user notification email with login credentials', 'mobility-trailblazers'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <p class="submit">
                        <button type="submit" name="mt_link_jury_user" class="button button-primary">
                            <?php _e('Link User Account', 'mobility-trailblazers'); ?>
                        </button>
                        <button type="button" class="button mt-cancel-link">
                            <?php _e('Cancel', 'mobility-trailblazers'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Unlink Form (Hidden) -->
            <div id="mt-unlink-user-form" style="display: none;">
                <h3><?php _e('Unlink User Account', 'mobility-trailblazers'); ?></h3>
                
                <form method="post" action="">
                    <?php wp_nonce_field('mt_unlink_jury_user', 'mt_jury_unlink_nonce'); ?>
                    <input type="hidden" name="jury_member_id" id="unlink-jury-member-id" value="">
                    
                    <p><?php _e('Are you sure you want to unlink this user account from the jury member?', 'mobility-trailblazers'); ?></p>
                    
                    <label>
                        <input type="checkbox" name="remove_role" value="1">
                        <?php _e('Also remove jury member role from user', 'mobility-trailblazers'); ?>
                    </label>
                    
                    <p class="submit">
                        <button type="submit" name="mt_unlink_jury_user" class="button button-primary">
                            <?php _e('Unlink User', 'mobility-trailblazers'); ?>
                        </button>
                        <button type="button" class="button mt-cancel-unlink">
                            <?php _e('Cancel', 'mobility-trailblazers'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <style>
        .mt-jury-linking-tool {
            background: #fff;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }

        .mt-tool-description {
            margin-bottom: 20px;
            padding: 10px;
            background: #f0f0f1;
            border-left: 4px solid #2271b1;
        }

        .mt-jury-members-list {
            margin-bottom: 30px;
        }

        .mt-link-options {
            margin: 20px 0;
        }

        .mt-link-options label {
            display: block;
            margin-bottom: 10px;
        }

        .mt-form-section {
            margin-top: 20px;
            padding: 20px;
            background: #f6f7f7;
            border: 1px solid #dcdcde;
        }

        #mt-link-user-form,
        #mt-unlink-user-form {
            margin-top: 20px;
            padding: 20px;
            background: #f6f7f7;
            border: 2px solid #2271b1;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Handle link user button click
            $('.mt-link-user').on('click', function() {
                var juryId = $(this).data('jury-id');
                var email = $(this).data('email');
                var name = $(this).data('name');
                
                $('#link-jury-member-id').val(juryId);
                $('#new_user_email').val(email);
                $('#new_user_display_name').val(name);
                
                // Generate username suggestion from name
                if (name) {
                    var username = name.toLowerCase().replace(/\s+/g, '.');
                    $('#new_user_login').val(username);
                }
                
                $('#mt-link-user-form').slideDown();
                $('html, body').animate({
                    scrollTop: $('#mt-link-user-form').offset().top - 50
                }, 500);
            });
            
            // Handle unlink user button click
            $('.mt-unlink-user').on('click', function() {
                var juryId = $(this).data('jury-id');
                $('#unlink-jury-member-id').val(juryId);
                $('#mt-unlink-user-form').slideDown();
                $('html, body').animate({
                    scrollTop: $('#mt-unlink-user-form').offset().top - 50
                }, 500);
            });
            
            // Handle cancel buttons
            $('.mt-cancel-link').on('click', function() {
                $('#mt-link-user-form').slideUp();
            });
            
            $('.mt-cancel-unlink').on('click', function() {
                $('#mt-unlink-user-form').slideUp();
            });
            
            // Toggle between existing and new user sections
            $('input[name="link_action"]').on('change', function() {
                if ($(this).val() === 'existing') {
                    $('#existing-user-section').show();
                    $('#new-user-section').hide();
                } else {
                    $('#existing-user-section').hide();
                    $('#new-user-section').show();
                }
            });
            
            // Validate form before submission
            $('form').on('submit', function(e) {
                var action = $('input[name="link_action"]:checked').val();
                
                if ($(this).find('button[name="mt_link_jury_user"]').length) {
                    if (action === 'existing') {
                        if (!$('#existing_user_id').val()) {
                            alert('<?php _e('Please select a user.', 'mobility-trailblazers'); ?>');
                            e.preventDefault();
                            return false;
                        }
                    } else if (action === 'create') {
                        if (!$('#new_user_login').val() || !$('#new_user_email').val()) {
                            alert('<?php _e('Please fill in all required fields.', 'mobility-trailblazers'); ?>');
                            e.preventDefault();
                            return false;
                        }
                    }
                }
            });
        });
        </script>
    </div>

    <!-- Jury Member Data Diagnostic and Repair Tool -->
    <?php
    /**
     * Jury Member Data Diagnostic and Repair Tool
     * Add this to your diagnostic.php to identify and fix data inconsistencies
     */

    // Security check
    if (!defined('ABSPATH')) {
        exit;
    }

    // Handle repair actions
    if (isset($_POST['mt_repair_jury_data']) && wp_verify_nonce($_POST['mt_repair_nonce'], 'mt_repair_jury_data')) {
        $action = sanitize_text_field($_POST['repair_action']);
        $repaired = 0;
        
        switch ($action) {
            case 'remove_orphaned_meta':
                // Remove user meta pointing to non-existent jury members
                $all_users = get_users();
                foreach ($all_users as $user) {
                    $jury_id = get_user_meta($user->ID, '_mt_jury_member_id', true);
                    if ($jury_id && !get_post($jury_id)) {
                        delete_user_meta($user->ID, '_mt_jury_member_id');
                        $repaired++;
                    }
                }
                echo '<div class="notice notice-success"><p>' . sprintf(__('Removed %d orphaned user meta entries.', 'mobility-trailblazers'), $repaired) . '</p></div>';
                break;
                
            case 'remove_invalid_links':
                // Remove jury meta pointing to non-existent users
                $jury_members = get_posts(array(
                    'post_type' => 'mt_jury_member',
                    'posts_per_page' => -1,
                    'post_status' => 'any'
                ));
                
                foreach ($jury_members as $jury) {
                    $user_id = get_post_meta($jury->ID, '_mt_user_id', true);
                    if ($user_id && !get_user_by('id', $user_id)) {
                        delete_post_meta($jury->ID, '_mt_user_id');
                        $repaired++;
                    }
                }
                echo '<div class="notice notice-success"><p>' . sprintf(__('Removed %d invalid jury member links.', 'mobility-trailblazers'), $repaired) . '</p></div>';
                break;
                
            case 'sync_bidirectional':
                // Ensure bidirectional links are consistent
                $jury_members = get_posts(array(
                    'post_type' => 'mt_jury_member',
                    'posts_per_page' => -1,
                    'post_status' => 'any'
                ));
                
                foreach ($jury_members as $jury) {
                    $user_id = get_post_meta($jury->ID, '_mt_user_id', true);
                    if ($user_id) {
                        $user = get_user_by('id', $user_id);
                        if ($user) {
                            $stored_jury_id = get_user_meta($user->ID, '_mt_jury_member_id', true);
                            if ($stored_jury_id != $jury->ID) {
                                update_user_meta($user_id, '_mt_jury_member_id', $jury->ID);
                                $repaired++;
                            }
                        }
                    }
                }
                echo '<div class="notice notice-success"><p>' . sprintf(__('Synchronized %d bidirectional links.', 'mobility-trailblazers'), $repaired) . '</p></div>';
                break;
        }
    }

    // Get diagnostic data
    $all_jury_members = get_posts(array(
        'post_type' => 'mt_jury_member',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'orderby' => 'title',
        'order' => 'ASC'
    ));

    $issues = array();
    $jury_data = array();

    foreach ($all_jury_members as $jury) {
        $user_id = get_post_meta($jury->ID, '_mt_user_id', true);
        $email = get_post_meta($jury->ID, '_mt_email', true);
        $user = $user_id ? get_user_by('id', $user_id) : null;
        
        $data = array(
            'jury' => $jury,
            'user_id' => $user_id,
            'user' => $user,
            'email' => $email,
            'issues' => array()
        );
        
        // Check for issues
        if ($user_id && !$user) {
            $data['issues'][] = 'User ID ' . $user_id . ' does not exist';
            $issues['invalid_user'][] = $jury->ID;
        }
        
        if ($user) {
            $stored_jury_id = get_user_meta($user->ID, '_mt_jury_member_id', true);
            if ($stored_jury_id != $jury->ID) {
                $data['issues'][] = 'Bidirectional link mismatch';
                $issues['link_mismatch'][] = $jury->ID;
            }
            
            if (!in_array('mt_jury_member', $user->roles)) {
                $data['issues'][] = 'User missing jury member role';
                $issues['missing_role'][] = $jury->ID;
            }
        }
        
        if (!$user_id) {
            $issues['no_user'][] = $jury->ID;
        }
        
        $jury_data[] = $data;
    }

    // Check for orphaned user meta
    $users_with_jury_meta = get_users(array(
        'meta_key' => '_mt_jury_member_id',
        'meta_compare' => 'EXISTS'
    ));

    foreach ($users_with_jury_meta as $user) {
        $jury_id = get_user_meta($user->ID, '_mt_jury_member_id', true);
        if (!get_post($jury_id)) {
            $issues['orphaned_meta'][] = $user->ID;
        }
    }
    ?>

    <div class="mt-jury-diagnostic">
        <h2><?php _e('Jury Member Data Diagnostic', 'mobility-trailblazers'); ?></h2>
        
        <!-- Summary -->
        <div class="mt-diagnostic-summary">
            <h3><?php _e('Summary', 'mobility-trailblazers'); ?></h3>
            <ul>
                <li><?php printf(__('Total Jury Members: %d', 'mobility-trailblazers'), count($all_jury_members)); ?></li>
                <li><?php printf(__('Linked to Users: %d', 'mobility-trailblazers'), count($all_jury_members) - count($issues['no_user'] ?? array())); ?></li>
                <li><?php printf(__('Not Linked: %d', 'mobility-trailblazers'), count($issues['no_user'] ?? array())); ?></li>
                <li class="<?php echo !empty($issues) ? 'has-issues' : 'no-issues'; ?>">
                    <?php 
                    $total_issues = array_sum(array_map('count', $issues));
                    printf(__('Total Issues Found: %d', 'mobility-trailblazers'), $total_issues); 
                    ?>
                </li>
            </ul>
        </div>
        
        <?php if (!empty($issues)) : ?>
        <!-- Issues Found -->
        <div class="mt-diagnostic-issues">
            <h3><?php _e('Issues Found', 'mobility-trailblazers'); ?></h3>
            
            <?php if (!empty($issues['invalid_user'])) : ?>
            <div class="issue-group">
                <h4><?php _e('Invalid User References', 'mobility-trailblazers'); ?></h4>
                <p><?php printf(__('%d jury members linked to non-existent users', 'mobility-trailblazers'), count($issues['invalid_user'])); ?></p>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('mt_repair_jury_data', 'mt_repair_nonce'); ?>
                    <input type="hidden" name="repair_action" value="remove_invalid_links">
                    <button type="submit" name="mt_repair_jury_data" class="button button-secondary">
                        <?php _e('Remove Invalid Links', 'mobility-trailblazers'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($issues['link_mismatch'])) : ?>
            <div class="issue-group">
                <h4><?php _e('Bidirectional Link Mismatches', 'mobility-trailblazers'); ?></h4>
                <p><?php printf(__('%d jury members with inconsistent user links', 'mobility-trailblazers'), count($issues['link_mismatch'])); ?></p>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('mt_repair_jury_data', 'mt_repair_nonce'); ?>
                    <input type="hidden" name="repair_action" value="sync_bidirectional">
                    <button type="submit" name="mt_repair_jury_data" class="button button-secondary">
                        <?php _e('Sync Links', 'mobility-trailblazers'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($issues['orphaned_meta'])) : ?>
            <div class="issue-group">
                <h4><?php _e('Orphaned User Meta', 'mobility-trailblazers'); ?></h4>
                <p><?php printf(__('%d users linked to non-existent jury members', 'mobility-trailblazers'), count($issues['orphaned_meta'])); ?></p>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('mt_repair_jury_data', 'mt_repair_nonce'); ?>
                    <input type="hidden" name="repair_action" value="remove_orphaned_meta">
                    <button type="submit" name="mt_repair_jury_data" class="button button-secondary">
                        <?php _e('Clean Orphaned Meta', 'mobility-trailblazers'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Detailed Data -->
        <div class="mt-diagnostic-details">
            <h3><?php _e('Detailed Jury Member Data', 'mobility-trailblazers'); ?></h3>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Email', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Linked User', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('User Exists', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Has Role', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Bidirectional', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Issues', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jury_data as $data) : ?>
                    <tr class="<?php echo !empty($data['issues']) ? 'has-issues' : ''; ?>">
                        <td>
                            <strong><?php echo esc_html($data['jury']->post_title); ?></strong>
                            <br>
                            <span class="description">ID: <?php echo $data['jury']->ID; ?></span>
                        </td>
                        <td>
                            <?php echo $data['email'] ? esc_html($data['email']) : '<em>No email</em>'; ?>
                        </td>
                        <td>
                            <?php if ($data['user']) : ?>
                                <a href="<?php echo get_edit_user_link($data['user']->ID); ?>">
                                    <?php echo esc_html($data['user']->display_name); ?>
                                </a>
                                <br>
                                <span class="description">ID: <?php echo $data['user']->ID; ?></span>
                            <?php else : ?>
                                <em><?php _e('Not linked', 'mobility-trailblazers'); ?></em>
                            <?php endif; ?>
                        </td>
                        <td class="status-column">
                            <?php if ($data['user_id']) : ?>
                                <?php if ($data['user']) : ?>
                                    <span class="dashicons dashicons-yes" style="color: #46b450;"></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-no" style="color: #dc3232;"></span>
                                <?php endif; ?>
                            <?php else : ?>
                                <span class="dashicons dashicons-minus" style="color: #666;"></span>
                            <?php endif; ?>
                        </td>
                        <td class="status-column">
                            <?php if ($data['user'] && in_array('mt_jury_member', $data['user']->roles)) : ?>
                                <span class="dashicons dashicons-yes" style="color: #46b450;"></span>
                            <?php elseif ($data['user']) : ?>
                                <span class="dashicons dashicons-no" style="color: #dc3232;"></span>
                            <?php else : ?>
                                <span class="dashicons dashicons-minus" style="color: #666;"></span>
                            <?php endif; ?>
                        </td>
                        <td class="status-column">
                            <?php 
                            if ($data['user']) {
                                $stored_jury_id = get_user_meta($data['user']->ID, '_mt_jury_member_id', true);
                                if ($stored_jury_id == $data['jury']->ID) {
                                    echo '<span class="dashicons dashicons-yes" style="color: #46b450;"></span>';
                                } else {
                                    echo '<span class="dashicons dashicons-no" style="color: #dc3232;"></span>';
                                }
                            } else {
                                echo '<span class="dashicons dashicons-minus" style="color: #666;"></span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($data['issues'])) : ?>
                                <ul class="issue-list">
                                    <?php foreach ($data['issues'] as $issue) : ?>
                                        <li><?php echo esc_html($issue); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <span style="color: #46b450;"><?php _e('OK', 'mobility-trailblazers'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Debug Information -->
        <div class="mt-diagnostic-debug">
            <h3><?php _e('Debug Information', 'mobility-trailblazers'); ?></h3>
            <button type="button" class="button" id="toggle-debug-info"><?php _e('Show Debug Data', 'mobility-trailblazers'); ?></button>
            
            <div id="debug-info" style="display: none;">
                <h4><?php _e('Unlinked Jury Members (for bulk tool)', 'mobility-trailblazers'); ?></h4>
                <?php
                $unlinked = get_posts(array(
                    'post_type' => 'mt_jury_member',
                    'posts_per_page' => -1,
                    'post_status' => 'any',
                    'meta_query' => array(
                        array(
                            'key' => '_mt_user_id',
                            'compare' => 'NOT EXISTS'
                        )
                    )
                ));
                ?>
                <p><?php printf(__('Found %d unlinked jury members using NOT EXISTS query', 'mobility-trailblazers'), count($unlinked)); ?></p>
                
                <h4><?php _e('Alternative Query (checking empty values)', 'mobility-trailblazers'); ?></h4>
                <?php
                $possibly_unlinked = get_posts(array(
                    'post_type' => 'mt_jury_member',
                    'posts_per_page' => -1,
                    'post_status' => 'any',
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
                        )
                    )
                ));
                ?>
                <p><?php printf(__('Found %d possibly unlinked jury members including empty values', 'mobility-trailblazers'), count($possibly_unlinked)); ?></p>
            </div>
        </div>
    </div>

    <!-- Jury Member Query Diagnostic Tool -->
    <?php
    /**
     * Jury Member Query Diagnostic Tool
     * This will help identify why queries are returning different results
     */

    // Security check
    if (!defined('ABSPATH')) {
        exit;
    }

    // Try different query methods to find jury members
    $query_results = array();

    // Method 1: Basic query
    $query_results['basic'] = get_posts(array(
        'post_type' => 'mt_jury_member',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));

    // Method 2: WP_Query
    $wp_query = new WP_Query(array(
        'post_type' => 'mt_jury_member',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
    ));
    $query_results['wp_query'] = $wp_query->posts;

    // Method 3: Direct database query
    global $wpdb;
    $query_results['direct_db'] = $wpdb->get_results(
        "SELECT * FROM {$wpdb->posts} WHERE post_type = 'mt_jury_member'"
    );

    // Method 4: Check if post type is registered
    $post_types = get_post_types(array(), 'names');
    $is_registered = in_array('mt_jury_member', $post_types);

    // Method 5: Try different post type variations (in case of typo)
    $possible_types = array('mt_jury_member', 'jury_member', 'mt_jury', 'jury');
    $found_types = array();
    foreach ($possible_types as $type) {
        $count = wp_count_posts($type);
        if ($count && (array_sum((array)$count) > 0)) {
            $found_types[$type] = $count;
        }
    }

    // Method 6: Get all custom post types with "jury" in the name
    $all_post_types = get_post_types(array('_builtin' => false), 'objects');
    $jury_related_types = array();
    foreach ($all_post_types as $type => $obj) {
        if (stripos($type, 'jury') !== false || stripos($obj->label, 'jury') !== false) {
            $jury_related_types[$type] = array(
                'label' => $obj->label,
                'count' => wp_count_posts($type)
            );
        }
    }

    // Method 7: Check for posts with jury member meta
    $posts_with_jury_meta = $wpdb->get_results(
        "SELECT DISTINCT p.* 
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE pm.meta_key IN ('_mt_email', '_mt_user_id', '_mt_company', '_mt_position')
         AND p.post_status != 'trash'"
    );

    // Get unlinked jury members with different methods
    $unlinked_queries = array();

    // Standard NOT EXISTS query
    $unlinked_queries['not_exists'] = get_posts(array(
        'post_type' => 'mt_jury_member',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'meta_query' => array(
            array(
                'key' => '_mt_user_id',
                'compare' => 'NOT EXISTS'
            )
        )
    ));

    // Including empty values
    $unlinked_queries['empty_values'] = get_posts(array(
        'post_type' => 'mt_jury_member',
        'posts_per_page' => -1,
        'post_status' => 'any',
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

    // Direct SQL for unlinked
    $unlinked_queries['direct_sql'] = $wpdb->get_results(
        "SELECT p.* 
         FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_mt_user_id'
         WHERE p.post_type = 'mt_jury_member' 
         AND p.post_status != 'trash'
         AND (pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = '0')"
    );
    ?>

    <div class="mt-query-diagnostic">
        <h2><?php _e('Jury Member Query Diagnostic', 'mobility-trailblazers'); ?></h2>
        
        <!-- Post Type Registration Check -->
        <div class="diagnostic-section">
            <h3><?php _e('Post Type Registration', 'mobility-trailblazers'); ?></h3>
            <p>
                Post type 'mt_jury_member' registered: 
                <strong><?php echo $is_registered ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>'; ?></strong>
            </p>
            
            <?php if (!empty($jury_related_types)) : ?>
            <h4><?php _e('Found Jury-Related Post Types:', 'mobility-trailblazers'); ?></h4>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Post Type</th>
                        <th>Label</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jury_related_types as $type => $data) : ?>
                    <tr>
                        <td><code><?php echo esc_html($type); ?></code></td>
                        <td><?php echo esc_html($data['label']); ?></td>
                        <td>
                            <?php 
                            $count = $data['count'];
                            if (is_object($count)) {
                                echo 'Publish: ' . $count->publish . ', ';
                                echo 'Draft: ' . $count->draft . ', ';
                                echo 'Private: ' . $count->private . ', ';
                                echo 'Trash: ' . $count->trash;
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Query Results Comparison -->
        <div class="diagnostic-section">
            <h3><?php _e('Query Method Results', 'mobility-trailblazers'); ?></h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Query Method</th>
                        <th>Results Found</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic get_posts()</td>
                        <td><?php echo count($query_results['basic']); ?></td>
                        <td>Standard query with post_status = 'any'</td>
                    </tr>
                    <tr>
                        <td>WP_Query</td>
                        <td><?php echo count($query_results['wp_query']); ?></td>
                        <td>All post statuses explicitly listed</td>
                    </tr>
                    <tr>
                        <td>Direct Database</td>
                        <td><?php echo count($query_results['direct_db']); ?></td>
                        <td>Raw SQL query for post_type = 'mt_jury_member'</td>
                    </tr>
                    <tr>
                        <td>Posts with Jury Meta</td>
                        <td><?php echo count($posts_with_jury_meta); ?></td>
                        <td>Any post with jury-related meta keys</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Found Post Types -->
        <?php if (!empty($found_types)) : ?>
        <div class="diagnostic-section">
            <h3><?php _e('Post Type Counts', 'mobility-trailblazers'); ?></h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Post Type</th>
                        <th>Status</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($found_types as $type => $counts) : ?>
                        <?php foreach ($counts as $status => $count) : ?>
                            <?php if ($count > 0) : ?>
                            <tr>
                                <td><code><?php echo esc_html($type); ?></code></td>
                                <td><?php echo esc_html($status); ?></td>
                                <td><?php echo esc_html($count); ?></td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Unlinked Queries Comparison -->
        <div class="diagnostic-section">
            <h3><?php _e('Unlinked Jury Members Query Results', 'mobility-trailblazers'); ?></h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Query Type</th>
                        <th>Count</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>NOT EXISTS</td>
                        <td><?php echo count($unlinked_queries['not_exists']); ?></td>
                        <td>Standard meta_query with NOT EXISTS</td>
                    </tr>
                    <tr>
                        <td>Empty Values</td>
                        <td><?php echo count($unlinked_queries['empty_values']); ?></td>
                        <td>Including empty strings, 0, false, null</td>
                    </tr>
                    <tr>
                        <td>Direct SQL</td>
                        <td><?php echo count($unlinked_queries['direct_sql']); ?></td>
                        <td>LEFT JOIN checking for NULL or empty</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Sample Data -->
        <?php if (!empty($posts_with_jury_meta)) : ?>
        <div class="diagnostic-section">
            <h3><?php _e('Sample Posts with Jury Meta (First 5)', 'mobility-trailblazers'); ?></h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Post Type</th>
                        <th>Status</th>
                        <th>Meta Keys</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sample_posts = array_slice($posts_with_jury_meta, 0, 5);
                    foreach ($sample_posts as $post) : 
                        $meta_keys = $wpdb->get_col($wpdb->prepare(
                            "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE post_id = %d",
                            $post->ID
                        ));
                    ?>
                    <tr>
                        <td><?php echo $post->ID; ?></td>
                        <td><?php echo esc_html($post->post_title); ?></td>
                        <td><code><?php echo esc_html($post->post_type); ?></code></td>
                        <td><?php echo esc_html($post->post_status); ?></td>
                        <td>
                            <?php 
                            $jury_meta = array_filter($meta_keys, function($key) {
                                return strpos($key, '_mt_') === 0;
                            });
                            echo implode(', ', array_map('esc_html', $jury_meta));
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Raw SQL Debug -->
        <div class="diagnostic-section">
            <h3><?php _e('Debug SQL Queries', 'mobility-trailblazers'); ?></h3>
            <button type="button" class="button" id="show-sql-queries"><?php _e('Show SQL Queries', 'mobility-trailblazers'); ?></button>
            
            <div id="sql-queries" style="display: none; margin-top: 15px;">
                <h4>Find all posts of type 'mt_jury_member':</h4>
                <pre style="background: #f0f0f1; padding: 10px; overflow-x: auto;">
SELECT * FROM <?php echo $wpdb->posts; ?> 
WHERE post_type = 'mt_jury_member' 
AND post_status != 'trash'</pre>
                
                <h4>Find all custom post types:</h4>
                <pre style="background: #f0f0f1; padding: 10px; overflow-x: auto;">
SELECT DISTINCT post_type 
FROM <?php echo $wpdb->posts; ?> 
WHERE post_type NOT IN ('post', 'page', 'attachment', 'revision', 'nav_menu_item')</pre>
                
                <h4>Find unlinked jury members:</h4>
                <pre style="background: #f0f0f1; padding: 10px; overflow-x: auto;">
SELECT p.* 
FROM <?php echo $wpdb->posts; ?> p
LEFT JOIN <?php echo $wpdb->postmeta; ?> pm ON p.ID = pm.post_id AND pm.meta_key = '_mt_user_id'
WHERE p.post_type = 'mt_jury_member' 
AND p.post_status != 'trash'
AND (pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = '0')"
                </pre>
            </div>
        </div>
        
        <!-- All Custom Post Types -->
        <div class="diagnostic-section">
            <h3><?php _e('All Registered Custom Post Types', 'mobility-trailblazers'); ?></h3>
            <?php
            $all_types = get_post_types(array('_builtin' => false), 'objects');
            if (!empty($all_types)) :
            ?>
            <ul>
                <?php foreach ($all_types as $type => $obj) : ?>
                    <li>
                        <code><?php echo esc_html($type); ?></code> - 
                        <?php echo esc_html($obj->label); ?>
                        (<?php 
                        $count = wp_count_posts($type);
                        echo isset($count->publish) ? $count->publish : 0;
                        ?> published)
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php else : ?>
            <p><?php _e('No custom post types found.', 'mobility-trailblazers'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .mt-query-diagnostic {
        background: #fff;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }

    .diagnostic-section {
        margin-bottom: 30px;
        padding: 20px;
        background: #f6f7f7;
        border: 1px solid #dcdcde;
    }

    .diagnostic-section h3 {
        margin-top: 0;
    }

    .diagnostic-section table {
        background: #fff;
    }

    pre {
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('#show-sql-queries').on('click', function() {
            $('#sql-queries').toggle();
            $(this).text(
                $('#sql-queries').is(':visible') 
                    ? '<?php _e('Hide SQL Queries', 'mobility-trailblazers'); ?>' 
                    : '<?php _e('Show SQL Queries', 'mobility-trailblazers'); ?>'
            );
        });
    });
    </script>
</div>
</div>

<style>
.mt-diagnostic-header {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-diagnostic-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-diagnostic-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.mt-diagnostic-category {
    margin-bottom: 30px;
}

.mt-diagnostic-category h3 {
    margin-bottom: 10px;
}

.mt-diagnostic-table {
    margin-bottom: 20px;
}

.mt-status-pass {
    color: #00a32a;
    font-weight: 600;
}

.mt-status-warning {
    color: #dba617;
    font-weight: 600;
}

.mt-status-fail {
    color: #d63638;
    font-weight: 600;
}

.mt-status-info {
    color: #2271b1;
    font-weight: 600;
}

.widefat th {
    width: 30%;
    font-weight: 600;
}

.widefat td {
    width: 70%;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Refresh diagnostic
    $('#refresh-diagnostic').on('click', function() {
        location.reload();
    });
    
    // Export diagnostic report
    $('#export-diagnostic').on('click', function() {
        var content = $('.wrap').html();
        var blob = new Blob([content], { type: 'text/html' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'mt-diagnostic-report-' + new Date().toISOString().slice(0, 10) + '.html';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
});
</script> 