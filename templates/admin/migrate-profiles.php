<?php
/**
 * Migrate Profiles Page Template
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="notice notice-info">
        <p><?php esc_html_e('This tool allows you to migrate candidate profiles from the old system to the new enhanced profile system.', 'mobility-trailblazers'); ?></p>
    </div>
    
    <div class="mt-migrate-container">
        <div class="card">
            <h2><?php esc_html_e('Migration Status', 'mobility-trailblazers'); ?></h2>
            
            <?php
            // Check if migration is needed
            global $wpdb;
            $candidates = get_posts([
                'post_type' => 'mt_candidate',
                'posts_per_page' => -1,
                'post_status' => 'any'
            ]);
            
            $total_candidates = count($candidates);
            $migrated = 0;
            $pending = 0;
            
            foreach ($candidates as $candidate) {
                if (get_post_meta($candidate->ID, 'profile_migrated', true) === 'yes') {
                    $migrated++;
                } else {
                    $pending++;
                }
            }
            ?>
            
            <table class="wp-list-table widefat">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Total Candidates', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($total_candidates); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Already Migrated', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($migrated); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Pending Migration', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($pending); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <?php if ($pending > 0): ?>
            <form method="post" action="" id="mt-migrate-form">
                <?php wp_nonce_field('mt_migrate_profiles', 'mt_migrate_nonce'); ?>
                
                <h3><?php esc_html_e('Migration Options', 'mobility-trailblazers'); ?></h3>
                
                <p>
                    <label>
                        <input type="checkbox" name="backup_first" value="1" checked>
                        <?php esc_html_e('Create backup before migration', 'mobility-trailblazers'); ?>
                    </label>
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="dry_run" value="1">
                        <?php esc_html_e('Dry run (simulate migration without making changes)', 'mobility-trailblazers'); ?>
                    </label>
                </p>
                
                <p class="submit">
                    <button type="submit" name="migrate_profiles" class="button button-primary">
                        <?php esc_html_e('Start Migration', 'mobility-trailblazers'); ?>
                    </button>
                </p>
            </form>
            <?php else: ?>
            <div class="notice notice-success inline">
                <p><?php esc_html_e('All profiles have been migrated successfully!', 'mobility-trailblazers'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2><?php esc_html_e('Migration Log', 'mobility-trailblazers'); ?></h2>
            <div id="migration-log" style="max-height: 400px; overflow-y: auto; padding: 10px; background: #f0f0f0;">
                <p><?php esc_html_e('Migration log will appear here...', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#mt-migrate-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $log = $('#migration-log');
        
        $button.prop('disabled', true).text('<?php esc_html_e('Migrating...', 'mobility-trailblazers'); ?>');
        $log.html('<p><?php esc_html_e('Starting migration...', 'mobility-trailblazers'); ?></p>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mt_migrate_profiles',
                nonce: $('#mt_migrate_nonce').val(),
                backup_first: $('input[name="backup_first"]').is(':checked') ? 1 : 0,
                dry_run: $('input[name="dry_run"]').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    $log.html('<p class="success">' + response.data.message + '</p>');
                    if (response.data.log) {
                        $log.append('<pre>' + response.data.log + '</pre>');
                    }
                    // Reload page after successful migration
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $log.html('<p class="error">' + response.data + '</p>');
                }
            },
            error: function() {
                $log.html('<p class="error"><?php esc_html_e('Migration failed. Please try again.', 'mobility-trailblazers'); ?></p>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php esc_html_e('Start Migration', 'mobility-trailblazers'); ?>');
            }
        });
    });
});
</script>