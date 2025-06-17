<?php
/**
 * Fix Capabilities Admin View
 *
 * @package MobilityTrailblazers
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is administrator
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
}

$message = '';

// Fix capabilities if requested
if (isset($_POST['fix_capabilities']) && wp_verify_nonce($_POST['mt_fix_nonce'], 'mt_fix_capabilities')) {
    $admin_role = get_role('administrator');
    
    if ($admin_role) {
        // Add all MT capabilities to administrator
        $capabilities = array(
            // Custom capabilities
            'mt_manage_awards',
            'mt_manage_assignments',
            'mt_view_all_evaluations',
            'mt_manage_voting',
            'mt_export_data',
            'mt_manage_jury_members',
            'mt_submit_evaluations',
            'mt_view_candidates',
            'mt_access_jury_dashboard',
            'mt_export_own_evaluations',
            'mt_reset_votes',
            'mt_create_backups',
            'mt_restore_backups',
            
            // Post type capabilities
            'edit_mt_candidate',
            'read_mt_candidate',
            'delete_mt_candidate',
            'edit_mt_candidates',
            'edit_others_mt_candidates',
            'publish_mt_candidates',
            'read_private_mt_candidates',
            'delete_mt_candidates',
            'delete_private_mt_candidates',
            'delete_published_mt_candidates',
            'delete_others_mt_candidates',
            'edit_private_mt_candidates',
            'edit_published_mt_candidates',
            
            'edit_mt_jury',
            'read_mt_jury',
            'delete_mt_jury',
            'edit_mt_jurys',
            'edit_others_mt_jurys',
            'publish_mt_jurys',
            'read_private_mt_jurys',
            'delete_mt_jurys',
            'delete_private_mt_jurys',
            'delete_published_mt_jurys',
            'delete_others_mt_jurys',
            'edit_private_mt_jurys',
            'edit_published_mt_jurys',
            
            'edit_mt_backup',
            'read_mt_backup',
            'delete_mt_backup',
            'edit_mt_backups',
            'edit_others_mt_backups',
            'publish_mt_backups',
            'read_private_mt_backups',
            'delete_mt_backups',
            'delete_private_mt_backups',
            'delete_published_mt_backups',
            'delete_others_mt_backups',
            'edit_private_mt_backups',
            'edit_published_mt_backups',
        );
        
        $added = 0;
        foreach ($capabilities as $cap) {
            $admin_role->add_cap($cap);
            $added++;
        }
        
        $message = sprintf(__('Successfully added %d capabilities to the administrator role.', 'mobility-trailblazers'), $added);
        
        // Clear any cached capabilities
        wp_cache_delete('user_roles', 'options');
        
        // Also ensure the custom roles exist
        if (!get_role('mt_award_admin')) {
            MT_Roles::create_roles();
            $message .= ' ' . __('Custom roles were also created.', 'mobility-trailblazers');
        }
    } else {
        $message = __('Error: Administrator role not found.', 'mobility-trailblazers');
    }
}

// Check current status
$admin_role = get_role('administrator');
$missing_caps = array();
$required_caps = array('mt_manage_awards', 'mt_submit_evaluations', 'mt_reset_votes');

foreach ($required_caps as $cap) {
    if (!$admin_role || !isset($admin_role->capabilities[$cap]) || !$admin_role->capabilities[$cap]) {
        $missing_caps[] = $cap;
    }
}
?>

<div class="wrap">
    <h1><?php _e('Fix Capabilities', 'mobility-trailblazers'); ?></h1>
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($missing_caps)): ?>
        <div class="notice notice-warning">
            <p><strong><?php _e('Missing Capabilities Detected:', 'mobility-trailblazers'); ?></strong></p>
            <ul>
                <?php foreach ($missing_caps as $cap): ?>
                    <li><?php echo esc_html($cap); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('mt_fix_capabilities', 'mt_fix_nonce'); ?>
            <p><?php _e('Click the button below to add all required capabilities to the administrator role.', 'mobility-trailblazers'); ?></p>
            <p class="submit">
                <input type="submit" name="fix_capabilities" class="button button-primary" value="<?php _e('Fix Capabilities', 'mobility-trailblazers'); ?>" />
            </p>
        </form>
    <?php else: ?>
        <div class="notice notice-success">
            <p><?php _e('All required capabilities are properly assigned to the administrator role.', 'mobility-trailblazers'); ?></p>
        </div>
        <p>
            <a href="<?php echo admin_url('admin.php?page=mt-diagnostic'); ?>" class="button">
                <?php _e('Return to Diagnostic', 'mobility-trailblazers'); ?>
            </a>
        </p>
    <?php endif; ?>
</div> 