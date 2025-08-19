<?php
/**
 * Email Template: Evaluation Reminder
 * 
 * Available variables:
 * $jury_name - Jury member's name
 * $pending_count - Number of pending evaluations
 * $dashboard_url - Link to jury dashboard
 * $deadline - Evaluation deadline
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Evaluation Reminder', 'mobility-trailblazers'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #26a69a;
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .button {
            display: inline-block;
            background: #26a69a;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 14px;
            color: #666;
        }
        .highlight {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ff9800;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php _e('25 Mobility Trailblazers in 25', 'mobility-trailblazers'); ?></h1>
    </div>
    
    <div class="content">
        <p><?php printf(__('Dear %s,', 'mobility-trailblazers'), esc_html($jury_name)); ?></p>
        
        <p><?php _e('This is a friendly reminder about your pending evaluations for the Mobility Trailblazers awards.', 'mobility-trailblazers'); ?></p>
        
        <div class="highlight">
            <strong><?php _e('Status:', 'mobility-trailblazers'); ?></strong><br>
            <?php printf(
                __('You have %d candidate(s) awaiting your evaluation.', 'mobility-trailblazers'),
                intval($pending_count)
            ); ?>
            <?php if (!empty($deadline)) : ?>
                <br><strong><?php _e('Deadline:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($deadline); ?>
            <?php endif; ?>
        </div>
        
        <p><?php _e('Your expertise and insights are invaluable in recognizing the innovators shaping the future of mobility in the DACH region.', 'mobility-trailblazers'); ?></p>
        
        <p style="text-align: center;">
            <a href="<?php echo esc_url($dashboard_url); ?>" class="button">
                <?php _e('Complete Your Evaluations', 'mobility-trailblazers'); ?>
            </a>
        </p>
        
        <p><?php _e('Thank you for your continued dedication to this important initiative.', 'mobility-trailblazers'); ?></p>
        
        <p>
            <?php _e('Best regards,', 'mobility-trailblazers'); ?><br>
            <?php _e('The Mobility Trailblazers Team', 'mobility-trailblazers'); ?>
        </p>
    </div>
    
    <div class="footer">
        <p><?php _e('This is an automated reminder from the Mobility Trailblazers evaluation platform.', 'mobility-trailblazers'); ?></p>
        <p><?php printf(
            __('If you have any questions, please contact us at %s', 'mobility-trailblazers'),
            '<a href="mailto:' . get_option('admin_email') . '">' . get_option('admin_email') . '</a>'
        ); ?></p>
    </div>
</body>
</html>