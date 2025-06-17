<?php
/**
 * Diagnostic Admin View - Complete Version
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

// Handle form submissions for jury linking
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
    'post_type' => mt_get_jury_post_type(),
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

<div class="wrap">
    <h1><?php _e('System Diagnostic', 'mobility-trailblazers'); ?></h1>
    
    <!-- Tabs Navigation -->
    <nav class="nav-tab-wrapper">
        <a href="#system-info" class="nav-tab nav-tab-active" data-tab="system-info"><?php _e('System Info', 'mobility-trailblazers'); ?></a>
        <a href="#diagnostic-results" class="nav-tab" data-tab="diagnostic-results"><?php _e('Diagnostic Results', 'mobility-trailblazers'); ?></a>
        <a href="#jury-linking" class="nav-tab" data-tab="jury-linking"><?php _e('Jury User Linking', 'mobility-trailblazers'); ?></a>
        <a href="#bulk-creation" class="nav-tab" data-tab="bulk-creation"><?php _e('Bulk User Creation', 'mobility-trailblazers'); ?></a>
    </nav>
    
    <!-- System Information Tab -->
    <div id="system-info" class="tab-content active">
        <div class="mt-diagnostic-header">
            <p><?php _e('This page displays comprehensive system health checks and diagnostic information for the Mobility Trailblazers plugin.', 'mobility-trailblazers'); ?></p>
            <button class="button button-primary" id="refresh-diagnostic"><?php _e('Refresh Diagnostic', 'mobility-trailblazers'); ?></button>
            <button class="button" id="export-diagnostic"><?php _e('Export Report', 'mobility-trailblazers'); ?></button>
        </div>

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
                        <th><?php _e('Active Theme', 'mobility-trailblazers'); ?></th>
                        <td><?php echo wp_get_theme()->get('Name') . ' v' . wp_get_theme()->get('Version'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Site URL', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_url(home_url()); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Diagnostic Results Tab -->
    <div id="diagnostic-results" class="tab-content">
        <div class="mt-diagnostic-section">
            <h2><?php _e('Diagnostic Results', 'mobility-trailblazers'); ?></h2>
            
            <?php foreach ($results as $category => $checks) : ?>
                <h3><?php echo esc_html(ucfirst($category)); ?></h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Check', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Message', 'mobility-trailblazers'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checks as $check) : ?>
                            <tr>
                                <td><?php echo isset($check['name']) ? esc_html($check['name']) : __('Unnamed Check', 'mobility-trailblazers'); ?></td>
                                <td>
                                    <?php
                                    $status_icon = '';
                                    $status_color = '';
                                    $status = isset($check['status']) ? $check['status'] : 'warning';
                                    switch ($status) {
                                        case 'success':
                                            $status_icon = 'dashicons-yes';
                                            $status_color = '#46b450';
                                            break;
                                        case 'warning':
                                            $status_icon = 'dashicons-warning';
                                            $status_color = '#ffb900';
                                            break;
                                        case 'error':
                                            $status_icon = 'dashicons-no';
                                            $status_color = '#dc3232';
                                            break;
                                    }
                                    ?>
                                    <span class="dashicons <?php echo $status_icon; ?>" style="color: <?php echo $status_color; ?>;"></span>
                                </td>
                                <td><?php echo isset($check['message']) ? esc_html($check['message']) : __('No message available', 'mobility-trailblazers'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
            
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
                <p>
                    <a href="<?php echo admin_url('admin.php?page=mt-fix-capabilities'); ?>" class="button button-secondary">
                        <?php _e('Fix Capabilities', 'mobility-trailblazers'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Jury User Linking Tab -->
    <div id="jury-linking" class="tab-content">
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
    </div>
    
    <!-- Bulk User Creation Tab -->
    <div id="bulk-creation" class="tab-content">
        <?php
        // Get unlinked jury members for bulk creation
        $unlinked_jury_members = get_posts(array(
            'post_type' => mt_get_jury_post_type(),
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
        ?>
        
        <div class="mt-bulk-creation-tool">
            <h2><?php _e('Bulk Create User Accounts for Jury Members', 'mobility-trailblazers'); ?></h2>
            
            <?php if (empty($unlinked_jury_members)) : ?>
                <p><?php _e('All jury members are already linked to user accounts.', 'mobility-trailblazers'); ?></p>
            <?php else : ?>
                <p><?php printf(__('Found %d jury members without user accounts.', 'mobility-trailblazers'), count($unlinked_jury_members)); ?></p>
                
                <div class="mt-bulk-actions">
                    <h3><?php _e('Quick Actions', 'mobility-trailblazers'); ?></h3>
                    <p>
                        <button class="button button-primary" id="bulk-create-all">
                            <?php _e('Create User Accounts for All Unlinked Jury Members', 'mobility-trailblazers'); ?>
                        </button>
                    </p>
                    <p class="description">
                        <?php _e('This will create user accounts for all jury members who don\'t have one yet. Usernames will be generated from their names, and passwords will be automatically generated and emailed.', 'mobility-trailblazers'); ?>
                    </p>
                </div>
                
                <div class="mt-unlinked-list">
                    <h3><?php _e('Unlinked Jury Members', 'mobility-trailblazers'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="bulk-select-all"></th>
                                <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                                <th><?php _e('Email', 'mobility-trailblazers'); ?></th>
                                <th><?php _e('Suggested Username', 'mobility-trailblazers'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unlinked_jury_members as $jury_member) : 
                                $email = get_post_meta($jury_member->ID, '_mt_email', true);
                                $suggested_username = strtolower(str_replace(' ', '.', $jury_member->post_title));
                            ?>
                            <tr>
                                <td><input type="checkbox" class="bulk-select-jury" value="<?php echo $jury_member->ID; ?>"></td>
                                <td><?php echo esc_html($jury_member->post_title); ?></td>
                                <td><?php echo $email ? esc_html($email) : '<em>No email set</em>'; ?></td>
                                <td><?php echo esc_html($suggested_username); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Select all checkbox
            $('#bulk-select-all').on('change', function() {
                $('.bulk-select-jury').prop('checked', $(this).is(':checked'));
            });
            
            // Bulk create all button
            $('#bulk-create-all').on('click', function() {
                if (!confirm('<?php _e('This will create user accounts for all unlinked jury members. Continue?', 'mobility-trailblazers'); ?>')) {
                    return;
                }
                
                var $button = $(this);
                $button.prop('disabled', true).text('<?php _e('Creating accounts...', 'mobility-trailblazers'); ?>');
                
                // In a real implementation, this would make an AJAX call
                // For now, show a message
                setTimeout(function() {
                    alert('<?php _e('Bulk user creation feature will be implemented via AJAX.', 'mobility-trailblazers'); ?>');
                    $button.prop('disabled', false).text('<?php _e('Create User Accounts for All Unlinked Jury Members', 'mobility-trailblazers'); ?>');
                }, 1000);
            });
        });
        </script>
    </div>
</div>

<style>
/* Tab Navigation */
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Diagnostic Sections */
.mt-diagnostic-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-diagnostic-header {
    margin-bottom: 20px;
}

/* Jury Linking Tool Styles */
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

/* Status Icons */
.dashicons-yes-alt,
.dashicons-yes {
    font-size: 20px;
}

.dashicons-warning,
.dashicons-no {
    font-size: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab Navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        $('.tab-content').removeClass('active');
        $('#' + tab).addClass('active');
        
        // Update URL hash
        window.location.hash = tab;
    });
    
    // Load tab from hash
    if (window.location.hash) {
        var hash = window.location.hash.substring(1);
        $('.nav-tab[data-tab="' + hash + '"]').click();
    }
    
    // Refresh button
    $('#refresh-diagnostic').on('click', function() {
        location.reload();
    });
    
    // Export button (placeholder)
    $('#export-diagnostic').on('click', function() {
        alert('Export functionality to be implemented');
    });
    
    // Jury Linking Tool Scripts
    
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