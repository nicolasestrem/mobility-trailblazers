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
            'candidates' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_candidate' AND post_status = 'publish'"),
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

    <!-- Jury Member User Account Linking Tool -->
    <?php
    /**
     * Jury Member User Account Linking Tool
     * Add this to your diagnostic.php view file 
     */

    // Security check
    if (!defined('ABSPATH')) {
        exit;
    }

    // Handle form submissions
    if (isset($_POST['mt_link_jury_user']) && wp_verify_nonce($_POST['mt_jury_link_nonce'], 'mt_link_jury_user')) {
        $jury_member_id = intval($_POST['jury_member_id']);
        $action = sanitize_text_field($_POST['link_action']);
        
        if ($action === 'existing') {
            // Link to existing user
            $user_id = intval($_POST['existing_user_id']);
            if ($user_id && $jury_member_id) {
                // Update the links
                update_post_meta($jury_member_id, '_mt_user_id', $user_id);
                update_user_meta($user_id, '_mt_jury_member_id', $jury_member_id);
                
                // Add jury member role to user
                $user = get_user_by('id', $user_id);
                if ($user && !in_array('mt_jury_member', $user->roles)) {
                    $user->add_role('mt_jury_member');
                }
                
                echo '<div class="notice notice-success"><p>' . __('Successfully linked jury member to user account.', 'mobility-trailblazers') . '</p></div>';
            }
        } elseif ($action === 'create') {
            // Create new user
            $email = sanitize_email($_POST['new_user_email']);
            $username = sanitize_user($_POST['new_user_login']);
            $display_name = sanitize_text_field($_POST['new_user_display_name']);
            
            if ($email && $username && $jury_member_id) {
                // Check if user already exists
                if (!username_exists($username) && !email_exists($email)) {
                    // Create the user
                    $user_data = array(
                        'user_login' => $username,
                        'user_email' => $email,
                        'display_name' => $display_name ?: $username,
                        'role' => 'mt_jury_member',
                        'user_pass' => wp_generate_password(12, true, true)
                    );
                    
                    $user_id = wp_insert_user($user_data);
                    
                    if (!is_wp_error($user_id)) {
                        // Link the user to jury member
                        update_post_meta($jury_member_id, '_mt_user_id', $user_id);
                        update_user_meta($user_id, '_mt_jury_member_id', $jury_member_id);
                        
                        // Update jury member email
                        update_post_meta($jury_member_id, '_mt_email', $email);
                        
                        // Send notification if requested
                        if (isset($_POST['send_notification'])) {
                            wp_new_user_notification($user_id, null, 'both');
                        }
                        
                        echo '<div class="notice notice-success"><p>' . __('Successfully created user account and linked to jury member.', 'mobility-trailblazers') . '</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>' . $user_id->get_error_message() . '</p></div>';
                    }
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Username or email already exists.', 'mobility-trailblazers') . '</p></div>';
                }
            }
        }
    } elseif (isset($_POST['mt_unlink_jury_user']) && wp_verify_nonce($_POST['mt_jury_unlink_nonce'], 'mt_unlink_jury_user')) {
        // Handle unlinking
        $jury_member_id = intval($_POST['jury_member_id']);
        $user_id = get_post_meta($jury_member_id, '_mt_user_id', true);
        
        if ($user_id) {
            // Remove the links
            delete_post_meta($jury_member_id, '_mt_user_id');
            delete_user_meta($user_id, '_mt_jury_member_id');
            
            // Optionally remove jury role
            if (isset($_POST['remove_role'])) {
                $user = get_user_by('id', $user_id);
                if ($user) {
                    $user->remove_role('mt_jury_member');
                }
            }
            
            echo '<div class="notice notice-success"><p>' . __('Successfully unlinked jury member from user account.', 'mobility-trailblazers') . '</p></div>';
        }
    }

    // Get all jury members
    $jury_members = get_posts(array(
        'post_type' => 'mt_jury_member',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'any'
    ));

    // Get all users who could be jury members
    $potential_users = get_users(array(
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));
    ?>

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