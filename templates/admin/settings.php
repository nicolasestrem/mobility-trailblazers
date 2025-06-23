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
    
    // Save dashboard settings
    if (isset($_POST['mt_dashboard_settings'])) {
        $dashboard_settings = [
            'header_style' => sanitize_text_field($_POST['mt_dashboard_settings']['header_style']),
            'primary_color' => sanitize_hex_color($_POST['mt_dashboard_settings']['primary_color']),
            'progress_bar_style' => sanitize_text_field($_POST['mt_dashboard_settings']['progress_bar_style']),
            'show_welcome_message' => isset($_POST['mt_dashboard_settings']['show_welcome_message']) ? 1 : 0,
            'show_progress_bar' => isset($_POST['mt_dashboard_settings']['show_progress_bar']) ? 1 : 0,
            'show_stats_cards' => isset($_POST['mt_dashboard_settings']['show_stats_cards']) ? 1 : 0,
            'show_search_filter' => isset($_POST['mt_dashboard_settings']['show_search_filter']) ? 1 : 0,
            'card_layout' => sanitize_text_field($_POST['mt_dashboard_settings']['card_layout']),
            'intro_text' => sanitize_textarea_field($_POST['mt_dashboard_settings']['intro_text']),
            'header_image_url' => sanitize_text_field($_POST['mt_dashboard_settings']['header_image_url'] ?? '')
        ];
        update_option('mt_dashboard_settings', $dashboard_settings);
    }
    
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

// Get dashboard settings
$dashboard_settings = get_option('mt_dashboard_settings', [
    'header_style' => 'gradient',
    'primary_color' => '#0073aa',
    'progress_bar_style' => 'rounded',
    'show_welcome_message' => 1,
    'show_progress_bar' => 1,
    'show_stats_cards' => 1,
    'show_search_filter' => 1,
    'card_layout' => 'grid',
    'intro_text' => __('Welcome to the Mobility Trailblazers Jury Dashboard. Here you can evaluate candidates and track your progress.', 'mobility-trailblazers')
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
        
        <!-- Jury Dashboard Customization -->
        <h2><?php _e('Jury Dashboard Customization', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Customize the appearance and behavior of the jury member dashboard.', 'mobility-trailblazers'); ?></p>

        <table class="form-table">
            <!-- Dashboard Header Customization -->
            <tr>
                <th scope="row">
                    <label for="dashboard_header_style"><?php _e('Dashboard Header Style', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <select id="dashboard_header_style" name="mt_dashboard_settings[header_style]">
                        <option value="gradient" <?php selected($dashboard_settings['header_style'], 'gradient'); ?>><?php _e('Gradient (Default)', 'mobility-trailblazers'); ?></option>
                        <option value="solid" <?php selected($dashboard_settings['header_style'], 'solid'); ?>><?php _e('Solid Color', 'mobility-trailblazers'); ?></option>
                        <option value="image" <?php selected($dashboard_settings['header_style'], 'image'); ?>><?php _e('Background Image', 'mobility-trailblazers'); ?></option>
                    </select>
                    <p class="description"><?php _e('Choose the visual style for the dashboard header.', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            
            <!-- Color Scheme -->
            <tr>
                <th scope="row">
                    <label for="dashboard_primary_color"><?php _e('Primary Color', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="color" id="dashboard_primary_color" name="mt_dashboard_settings[primary_color]" 
                           value="<?php echo esc_attr($dashboard_settings['primary_color']); ?>" />
                    <p class="description"><?php _e('Main accent color used throughout the dashboard.', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            
            <!-- Header Background Image -->
            <tr>
                <th scope="row">
                    <label for="header_image_url"><?php _e('Header Background Image', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="text" id="header_image_url" name="mt_dashboard_settings[header_image_url]" 
                           value="<?php echo esc_attr($dashboard_settings['header_image_url'] ?? ''); ?>" 
                           class="regular-text" />
                    <button type="button" id="upload_header_image" class="button button-secondary">
                        <?php _e('Choose Image', 'mobility-trailblazers'); ?>
                    </button>
                    <?php if (!empty($dashboard_settings['header_image_url'])) : ?>
                        <div class="mt-image-preview">
                            <img id="header_image_preview" src="<?php echo esc_url($dashboard_settings['header_image_url']); ?>" 
                                 alt="<?php _e('Header background preview', 'mobility-trailblazers'); ?>" 
                                 style="max-width: 200px; height: auto; margin-top: 10px;" />
                        </div>
                    <?php else : ?>
                        <div class="mt-image-preview" style="display: none;">
                            <img id="header_image_preview" src="" alt="<?php _e('Header background preview', 'mobility-trailblazers'); ?>" 
                                 style="max-width: 200px; height: auto; margin-top: 10px;" />
                        </div>
                    <?php endif; ?>
                    <p class="description"><?php _e('Upload or choose an image for the dashboard header background. Only used when header style is set to "Background Image".', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            
            <!-- Progress Bar Style -->
            <tr>
                <th scope="row">
                    <label for="progress_bar_style"><?php _e('Progress Bar Style', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <select id="progress_bar_style" name="mt_dashboard_settings[progress_bar_style]">
                        <option value="rounded" <?php selected($dashboard_settings['progress_bar_style'], 'rounded'); ?>><?php _e('Rounded', 'mobility-trailblazers'); ?></option>
                        <option value="square" <?php selected($dashboard_settings['progress_bar_style'], 'square'); ?>><?php _e('Square', 'mobility-trailblazers'); ?></option>
                        <option value="striped" <?php selected($dashboard_settings['progress_bar_style'], 'striped'); ?>><?php _e('Striped', 'mobility-trailblazers'); ?></option>
                    </select>
                </td>
            </tr>
            
            <!-- Display Options -->
            <tr>
                <th scope="row"><?php _e('Dashboard Elements', 'mobility-trailblazers'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="mt_dashboard_settings[show_welcome_message]" value="1" 
                               <?php checked($dashboard_settings['show_welcome_message'], 1); ?> />
                        <?php _e('Show personalized welcome message', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_dashboard_settings[show_progress_bar]" value="1" 
                               <?php checked($dashboard_settings['show_progress_bar'], 1); ?> />
                        <?php _e('Show progress bar in header', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_dashboard_settings[show_stats_cards]" value="1" 
                               <?php checked($dashboard_settings['show_stats_cards'], 1); ?> />
                        <?php _e('Show statistics cards', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_dashboard_settings[show_search_filter]" value="1" 
                               <?php checked($dashboard_settings['show_search_filter'], 1); ?> />
                        <?php _e('Enable search and filter functionality', 'mobility-trailblazers'); ?>
                    </label>
                </td>
            </tr>
            
            <!-- Cards Layout -->
            <tr>
                <th scope="row">
                    <label for="candidate_card_layout"><?php _e('Candidate Card Layout', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <select id="candidate_card_layout" name="mt_dashboard_settings[card_layout]">
                        <option value="grid" <?php selected($dashboard_settings['card_layout'], 'grid'); ?>><?php _e('Grid View', 'mobility-trailblazers'); ?></option>
                        <option value="list" <?php selected($dashboard_settings['card_layout'], 'list'); ?>><?php _e('List View', 'mobility-trailblazers'); ?></option>
                        <option value="compact" <?php selected($dashboard_settings['card_layout'], 'compact'); ?>><?php _e('Compact View', 'mobility-trailblazers'); ?></option>
                    </select>
                </td>
            </tr>
            
            <!-- Custom Messages -->
            <tr>
                <th scope="row">
                    <label for="dashboard_intro_text"><?php _e('Dashboard Introduction Text', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <textarea id="dashboard_intro_text" name="mt_dashboard_settings[intro_text]" rows="3" cols="50"><?php 
                        echo esc_textarea($dashboard_settings['intro_text']); 
                    ?></textarea>
                    <p class="description"><?php _e('Custom message displayed at the top of the jury dashboard.', 'mobility-trailblazers'); ?></p>
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