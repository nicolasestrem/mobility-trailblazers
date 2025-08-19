<?php
/**
 * Email Template: Assignment Notification
 * 
 * Available variables:
 * $jury_name - Jury member's name
 * $candidates - Array of assigned candidates
 * $dashboard_url - Link to jury dashboard
 * $total_assignments - Total number of assignments
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
    <title><?php _e('New Candidate Assignments', 'mobility-trailblazers'); ?></title>
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
        .candidate-list {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .candidate-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .candidate-item:last-child {
            border-bottom: none;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php _e('25 Mobility Trailblazers in 25', 'mobility-trailblazers'); ?></h1>
    </div>
    
    <div class="content">
        <p><?php printf(__('Dear %s,', 'mobility-trailblazers'), esc_html($jury_name)); ?></p>
        
        <p><?php printf(
            __('You have been assigned %d new candidate(s) to evaluate for the Mobility Trailblazers awards.', 'mobility-trailblazers'),
            intval($total_assignments)
        ); ?></p>
        
        <?php if (!empty($candidates)) : ?>
        <div class="candidate-list">
            <h3><?php _e('Your Assigned Candidates:', 'mobility-trailblazers'); ?></h3>
            <?php foreach ($candidates as $candidate) : ?>
            <div class="candidate-item">
                <strong><?php echo esc_html($candidate['name']); ?></strong>
                <?php if (!empty($candidate['organization'])) : ?>
                    <br><?php echo esc_html($candidate['organization']); ?>
                <?php endif; ?>
                <?php if (!empty($candidate['category'])) : ?>
                    <br><em><?php _e('Category:', 'mobility-trailblazers'); ?> <?php echo esc_html($candidate['category']); ?></em>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <p><?php _e('Please review each candidate thoroughly and provide your evaluation based on the following criteria:', 'mobility-trailblazers'); ?></p>
        
        <ul>
            <li><?php _e('Mut & Pioniergeist', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Innovationsgrad', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Umsetzungskraft & Wirkung', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Relevanz für die Mobilitätswende', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'); ?></li>
        </ul>
        
        <p style="text-align: center;">
            <a href="<?php echo esc_url($dashboard_url); ?>" class="button">
                <?php _e('Start Evaluating', 'mobility-trailblazers'); ?>
            </a>
        </p>
        
        <p><?php _e('Your expertise is crucial in identifying the mobility innovators who are making a real difference.', 'mobility-trailblazers'); ?></p>
        
        <p>
            <?php _e('Best regards,', 'mobility-trailblazers'); ?><br>
            <?php _e('The Mobility Trailblazers Team', 'mobility-trailblazers'); ?>
        </p>
    </div>
    
    <div class="footer">
        <p><?php _e('This notification was sent from the Mobility Trailblazers evaluation platform.', 'mobility-trailblazers'); ?></p>
        <p><?php printf(
            __('If you have any questions, please contact us at %s', 'mobility-trailblazers'),
            '<a href="mailto:' . get_option('admin_email') . '">' . get_option('admin_email') . '</a>'
        ); ?></p>
    </div>
</body>
</html>