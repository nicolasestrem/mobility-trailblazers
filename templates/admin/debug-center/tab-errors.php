<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mt-debug-errors">
    <div class="mt-debug-header">
        <h2><?php esc_html_e('Error Monitoring', 'mobility-trailblazers'); ?></h2>
        <p class="description">
            <?php esc_html_e('Error monitoring has been removed from this version.', 'mobility-trailblazers'); ?>
        </p>
    </div>

    <div class="mt-debug-section">
        <div class="notice notice-info">
            <p>
                <?php esc_html_e('The error monitoring feature has been deprecated and removed. Please use WordPress debug logging or your server error logs for error tracking.', 'mobility-trailblazers'); ?>
            </p>
            <p>
                <?php esc_html_e('To enable WordPress debug logging, add the following to your wp-config.php file:', 'mobility-trailblazers'); ?>
            </p>
            <pre style="background: #f0f0f0; padding: 10px; margin: 10px 0;">
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
            </pre>
            <p>
                <?php 
                printf(
                    esc_html__('Debug logs will be saved to: %s', 'mobility-trailblazers'),
                    '<code>' . esc_html(WP_CONTENT_DIR . '/debug.log') . '</code>'
                );
                ?>
            </p>
        </div>
    </div>
</div>