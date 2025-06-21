<?php
/**
 * Settings Admin View
 *
 * @package MobilityTrailblazers
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Save settings
if (isset($_POST['submit']) && wp_verify_nonce($_POST['mt_settings_nonce'], 'mt_save_settings')) {
    // General settings
    update_option('mt_current_award_year', sanitize_text_field($_POST['mt_current_award_year']));
    update_option('mt_current_phase', sanitize_text_field($_POST['mt_current_phase']));
    update_option('mt_enable_public_voting', isset($_POST['mt_enable_public_voting']) ? 1 : 0);
    update_option('mt_enable_registration', isset($_POST['mt_enable_registration']) ? 1 : 0);
    
    // Evaluation settings
    update_option('mt_min_evaluations_required', intval($_POST['mt_min_evaluations_required']));
    update_option('mt_evaluation_deadline', sanitize_text_field($_POST['mt_evaluation_deadline']));
    update_option('mt_enable_auto_reminders', isset($_POST['mt_enable_auto_reminders']) ? 1 : 0);
    update_option('mt_reminder_days_before', intval($_POST['mt_reminder_days_before']));
    
    // Email settings
    update_option('mt_email_from_name', sanitize_text_field($_POST['mt_email_from_name']));
    update_option('mt_email_from_address', sanitize_email($_POST['mt_email_from_address']));
    update_option('mt_email_footer', wp_kses_post($_POST['mt_email_footer']));
    
    // Display settings
    update_option('mt_candidates_per_page', intval($_POST['mt_candidates_per_page']));
    update_option('mt_enable_animations', isset($_POST['mt_enable_animations']) ? 1 : 0);
    update_option('mt_date_format', sanitize_text_field($_POST['mt_date_format']));
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'mobility-trailblazers') . '</p></div>';
}

// Get current settings
$current_year = get_option('mt_current_award_year', date('Y'));
$current_phase = get_option('mt_current_phase', 'nomination');
$enable_public_voting = get_option('mt_enable_public_voting', 0);
$enable_registration = get_option('mt_enable_registration', 1);
$min_evaluations = get_option('mt_min_evaluations_required', 3);
$evaluation_deadline = get_option('mt_evaluation_deadline', '');
$enable_reminders = get_option('mt_enable_auto_reminders', 1);
$reminder_days = get_option('mt_reminder_days_before', 7);
$email_from_name = get_option('mt_email_from_name', get_bloginfo('name'));
$email_from_address = get_option('mt_email_from_address', get_option('admin_email'));
$email_footer = get_option('mt_email_footer', '');
$candidates_per_page = get_option('mt_candidates_per_page', 12);
$enable_animations = get_option('mt_enable_animations', 1);
$date_format = get_option('mt_date_format', 'F j, Y');
?>

<div class="wrap">
    <h1><?php _e('Mobility Trailblazers Settings', 'mobility-trailblazers'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('mt_save_settings', 'mt_settings_nonce'); ?>
        
        <div class="mt-settings-tabs">
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'mobility-trailblazers'); ?></a>
                <a href="#evaluation" class="nav-tab"><?php _e('Evaluation', 'mobility-trailblazers'); ?></a>
                <a href="#email" class="nav-tab"><?php _e('Email', 'mobility-trailblazers'); ?></a>
                <a href="#display" class="nav-tab"><?php _e('Display', 'mobility-trailblazers'); ?></a>
            </h2>
            
            <!-- General Settings -->
            <div id="general" class="mt-settings-panel">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mt_current_award_year"><?php _e('Current Award Year', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="mt_current_award_year" name="mt_current_award_year" 
                                   value="<?php echo esc_attr($current_year); ?>" min="2024" max="2030" />
                            <p class="description"><?php _e('The year for the current award cycle', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mt_current_phase"><?php _e('Current Phase', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <select id="mt_current_phase" name="mt_current_phase">
                                <option value="nomination" <?php selected($current_phase, 'nomination'); ?>>
                                    <?php _e('Nomination', 'mobility-trailblazers'); ?>
                                </option>
                                <option value="evaluation" <?php selected($current_phase, 'evaluation'); ?>>
                                    <?php _e('Evaluation', 'mobility-trailblazers'); ?>
                                </option>
                                <option value="selection" <?php selected($current_phase, 'selection'); ?>>
                                    <?php _e('Selection', 'mobility-trailblazers'); ?>
                                </option>
                                <option value="announcement" <?php selected($current_phase, 'announcement'); ?>>
                                    <?php _e('Announcement', 'mobility-trailblazers'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('The current phase of the award process', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Public Features', 'mobility-trailblazers'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="mt_enable_public_voting" value="1" 
                                           <?php checked($enable_public_voting, 1); ?> />
                                    <?php _e('Enable public voting', 'mobility-trailblazers'); ?>
                                </label>
                                <br />
                                <label>
                                    <input type="checkbox" name="mt_enable_registration" value="1" 
                                           <?php checked($enable_registration, 1); ?> />
                                    <?php _e('Enable candidate registration', 'mobility-trailblazers'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Evaluation Settings -->
            <div id="evaluation" class="mt-settings-panel" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mt_min_evaluations_required"><?php _e('Minimum Evaluations Required', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="mt_min_evaluations_required" name="mt_min_evaluations_required" 
                                   value="<?php echo esc_attr($min_evaluations); ?>" min="1" max="10" />
                            <p class="description"><?php _e('Minimum number of jury evaluations required per candidate', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mt_evaluation_deadline"><?php _e('Evaluation Deadline', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <input type="date" id="mt_evaluation_deadline" name="mt_evaluation_deadline" 
                                   value="<?php echo esc_attr($evaluation_deadline); ?>" />
                            <p class="description"><?php _e('Deadline for jury members to complete evaluations', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Reminders', 'mobility-trailblazers'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mt_enable_auto_reminders" value="1" 
                                       <?php checked($enable_reminders, 1); ?> />
                                <?php _e('Enable automatic reminder emails', 'mobility-trailblazers'); ?>
                            </label>
                            <br /><br />
                            <label>
                                <?php _e('Send reminders', 'mobility-trailblazers'); ?>
                                <input type="number" name="mt_reminder_days_before" 
                                       value="<?php echo esc_attr($reminder_days); ?>" min="1" max="30" style="width: 60px;" />
                                <?php _e('days before deadline', 'mobility-trailblazers'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Email Settings -->
            <div id="email" class="mt-settings-panel" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mt_email_from_name"><?php _e('From Name', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="mt_email_from_name" name="mt_email_from_name" 
                                   value="<?php echo esc_attr($email_from_name); ?>" class="regular-text" />
                            <p class="description"><?php _e('Name that appears in the "From" field of emails', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mt_email_from_address"><?php _e('From Email', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="mt_email_from_address" name="mt_email_from_address" 
                                   value="<?php echo esc_attr($email_from_address); ?>" class="regular-text" />
                            <p class="description"><?php _e('Email address that appears in the "From" field', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mt_email_footer"><?php _e('Email Footer', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <?php 
                            wp_editor($email_footer, 'mt_email_footer', array(
                                'textarea_rows' => 5,
                                'media_buttons' => false,
                                'teeny' => true
                            ));
                            ?>
                            <p class="description"><?php _e('Footer text that appears in all system emails', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Display Settings -->
            <div id="display" class="mt-settings-panel" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mt_candidates_per_page"><?php _e('Candidates Per Page', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="mt_candidates_per_page" name="mt_candidates_per_page" 
                                   value="<?php echo esc_attr($candidates_per_page); ?>" min="6" max="50" />
                            <p class="description"><?php _e('Number of candidates to display per page in grids', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Visual Effects', 'mobility-trailblazers'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mt_enable_animations" value="1" 
                                       <?php checked($enable_animations, 1); ?> />
                                <?php _e('Enable animations and transitions', 'mobility-trailblazers'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mt_date_format"><?php _e('Date Format', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <select id="mt_date_format" name="mt_date_format">
                                <option value="F j, Y" <?php selected($date_format, 'F j, Y'); ?>>
                                    <?php echo date('F j, Y'); ?>
                                </option>
                                <option value="Y-m-d" <?php selected($date_format, 'Y-m-d'); ?>>
                                    <?php echo date('Y-m-d'); ?>
                                </option>
                                <option value="m/d/Y" <?php selected($date_format, 'm/d/Y'); ?>>
                                    <?php echo date('m/d/Y'); ?>
                                </option>
                                <option value="d/m/Y" <?php selected($date_format, 'd/m/Y'); ?>>
                                    <?php echo date('d/m/Y'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('How dates are displayed throughout the plugin', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" 
                   value="<?php _e('Save Settings', 'mobility-trailblazers'); ?>" />
        </p>
    </form>
</div>

<style>
.mt-settings-tabs {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.nav-tab-wrapper {
    margin: 0;
    padding: 0;
}

.mt-settings-panel {
    padding: 20px;
}

.form-table th {
    width: 250px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show target panel
        $('.mt-settings-panel').hide();
        $(target).show();
    });
});
</script> 