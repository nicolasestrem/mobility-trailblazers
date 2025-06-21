<?php
/**
 * Admin Settings Page Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Save settings
if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'mt_settings')) {
    // Save criteria weights
    $weights = [
        'courage' => floatval($_POST['weight_courage']),
        'innovation' => floatval($_POST['weight_innovation']),
        'implementation' => floatval($_POST['weight_implementation']),
        'relevance' => floatval($_POST['weight_relevance']),
        'visibility' => floatval($_POST['weight_visibility'])
    ];
    update_option('mt_criteria_weights', $weights);
    
    // Save other settings
    update_option('mt_enable_notifications', isset($_POST['enable_notifications']) ? 1 : 0);
    update_option('mt_notification_email', sanitize_email($_POST['notification_email']));
    update_option('mt_evaluations_per_page', intval($_POST['evaluations_per_page']));
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'mobility-trailblazers') . '</p></div>';
}

// Get current settings
$weights = get_option('mt_criteria_weights', [
    'courage' => 1,
    'innovation' => 1,
    'implementation' => 1,
    'relevance' => 1,
    'visibility' => 1
]);
$enable_notifications = get_option('mt_enable_notifications', 0);
$notification_email = get_option('mt_notification_email', get_option('admin_email'));
$evaluations_per_page = get_option('mt_evaluations_per_page', 10);
?>

<div class="wrap">
    <h1><?php _e('Mobility Trailblazers Settings', 'mobility-trailblazers'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('mt_settings'); ?>
        
        <!-- Evaluation Criteria Weights -->
        <h2><?php _e('Evaluation Criteria Weights', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Set the weight for each evaluation criterion. Higher weights give more importance to that criterion in the total score calculation.', 'mobility-trailblazers'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="weight_courage"><?php _e('Mut & Pioniergeist', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="number" name="weight_courage" id="weight_courage" 
                           value="<?php echo esc_attr($weights['courage']); ?>" 
                           min="0" max="10" step="0.1" class="small-text">
                    <p class="description"><?php _e('Weight for Courage & Pioneer Spirit criterion', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="weight_innovation"><?php _e('Innovationsgrad', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="number" name="weight_innovation" id="weight_innovation" 
                           value="<?php echo esc_attr($weights['innovation']); ?>" 
                           min="0" max="10" step="0.1" class="small-text">
                    <p class="description"><?php _e('Weight for Innovation Degree criterion', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="weight_implementation"><?php _e('Umsetzungskraft & Wirkung', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="number" name="weight_implementation" id="weight_implementation" 
                           value="<?php echo esc_attr($weights['implementation']); ?>" 
                           min="0" max="10" step="0.1" class="small-text">
                    <p class="description"><?php _e('Weight for Implementation & Impact criterion', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="weight_relevance"><?php _e('Relevanz für Mobilitätswende', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="number" name="weight_relevance" id="weight_relevance" 
                           value="<?php echo esc_attr($weights['relevance']); ?>" 
                           min="0" max="10" step="0.1" class="small-text">
                    <p class="description"><?php _e('Weight for Mobility Transformation Relevance criterion', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="weight_visibility"><?php _e('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="number" name="weight_visibility" id="weight_visibility" 
                           value="<?php echo esc_attr($weights['visibility']); ?>" 
                           min="0" max="10" step="0.1" class="small-text">
                    <p class="description"><?php _e('Weight for Role Model & Visibility criterion', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
        </table>
        
        <!-- Notification Settings -->
        <h2><?php _e('Notification Settings', 'mobility-trailblazers'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="enable_notifications"><?php _e('Enable Email Notifications', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_notifications" id="enable_notifications" 
                               value="1" <?php checked($enable_notifications, 1); ?>>
                        <?php _e('Send email notifications when evaluations are submitted', 'mobility-trailblazers'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="notification_email"><?php _e('Notification Email', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="email" name="notification_email" id="notification_email" 
                           value="<?php echo esc_attr($notification_email); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('Email address to receive notifications', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
        </table>
        
        <!-- Display Settings -->
        <h2><?php _e('Display Settings', 'mobility-trailblazers'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="evaluations_per_page"><?php _e('Evaluations Per Page', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="number" name="evaluations_per_page" id="evaluations_per_page" 
                           value="<?php echo esc_attr($evaluations_per_page); ?>" 
                           min="5" max="100" class="small-text">
                    <p class="description"><?php _e('Number of evaluations to display per page in admin', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
        </table>
        
        <!-- System Information -->
        <h2><?php _e('System Information', 'mobility-trailblazers'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Plugin Version', 'mobility-trailblazers'); ?></th>
                <td><?php echo esc_html(MT_VERSION); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Database Version', 'mobility-trailblazers'); ?></th>
                <td><?php echo esc_html(get_option('mt_db_version', '1.0')); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Total Candidates', 'mobility-trailblazers'); ?></th>
                <td><?php echo wp_count_posts('mt_candidate')->publish; ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Total Jury Members', 'mobility-trailblazers'); ?></th>
                <td><?php echo wp_count_posts('mt_jury_member')->publish; ?></td>
            </tr>
            <?php
            $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
            $stats = $evaluation_repo->get_statistics();
            ?>
            <tr>
                <th scope="row"><?php _e('Total Evaluations', 'mobility-trailblazers'); ?></th>
                <td><?php echo intval($stats['total']); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Completed Evaluations', 'mobility-trailblazers'); ?></th>
                <td><?php echo intval($stats['completed']); ?></td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" 
                   value="<?php esc_attr_e('Save Settings', 'mobility-trailblazers'); ?>">
        </p>
    </form>
</div> 