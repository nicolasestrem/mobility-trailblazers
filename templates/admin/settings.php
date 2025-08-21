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
    
    // Save data management settings
    update_option('mt_remove_data_on_uninstall', isset($_POST['mt_remove_data_on_uninstall']) ? '1' : '0');
    
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
            'show_rankings' => isset($_POST['mt_dashboard_settings']['show_rankings']) ? 1 : 0,
            'rankings_limit' => intval($_POST['mt_dashboard_settings']['rankings_limit']),
            'card_layout' => sanitize_text_field($_POST['mt_dashboard_settings']['card_layout']),
            'intro_text' => sanitize_textarea_field($_POST['mt_dashboard_settings']['intro_text']),
            'header_image_url' => sanitize_text_field($_POST['mt_dashboard_settings']['header_image_url'] ?? '')
        ];
        update_option('mt_dashboard_settings', $dashboard_settings);
    }
    
    // Save candidate presentation settings
    if (isset($_POST['mt_candidate_presentation'])) {
        $candidate_presentation = [
            'profile_layout' => sanitize_text_field($_POST['mt_candidate_presentation']['profile_layout']),
            'photo_style' => sanitize_text_field($_POST['mt_candidate_presentation']['photo_style']),
            'photo_size' => sanitize_text_field($_POST['mt_candidate_presentation']['photo_size']),
            'show_organization' => isset($_POST['mt_candidate_presentation']['show_organization']) ? 1 : 0,
            'show_position' => isset($_POST['mt_candidate_presentation']['show_position']) ? 1 : 0,
            'show_category' => isset($_POST['mt_candidate_presentation']['show_category']) ? 1 : 0,
            'show_innovation_summary' => isset($_POST['mt_candidate_presentation']['show_innovation_summary']) ? 1 : 0,
            'show_full_bio' => isset($_POST['mt_candidate_presentation']['show_full_bio']) ? 1 : 0,
            'form_style' => sanitize_text_field($_POST['mt_candidate_presentation']['form_style']),
            'scoring_style' => sanitize_text_field($_POST['mt_candidate_presentation']['scoring_style']),
            'enable_animations' => isset($_POST['mt_candidate_presentation']['enable_animations']) ? 1 : 0,
            'enable_hover_effects' => isset($_POST['mt_candidate_presentation']['enable_hover_effects']) ? 1 : 0
        ];
        update_option('mt_candidate_presentation', $candidate_presentation);
    }
    
    // Save enhanced template setting
    update_option('mt_use_enhanced_template', isset($_POST['mt_use_enhanced_template']) ? '1' : '0');
    
    // Save language settings
    if (isset($_POST['mt_default_language'])) {
        update_option('mt_default_language', sanitize_text_field($_POST['mt_default_language']));
    }
    update_option('mt_enable_language_switcher', isset($_POST['mt_enable_language_switcher']) ? '1' : '0');
    update_option('mt_auto_detect_language', isset($_POST['mt_auto_detect_language']) ? '1' : '0');
    
    // Save other settings
    update_option('mt_evaluations_per_page', intval($_POST['evaluations_per_page']));
    
    echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'mobility-trailblazers') . '</p></div>';
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
    'show_rankings' => 1,
    'rankings_limit' => 10,
    'card_layout' => 'grid',
    'intro_text' => __('Welcome to the Mobility Trailblazers Jury Dashboard. Here you can evaluate candidates and track your progress.', 'mobility-trailblazers'),
    'header_image_url' => ''
]);

// Get candidate presentation settings
$candidate_presentation = get_option('mt_candidate_presentation', [
    'profile_layout' => 'side-by-side',
    'photo_style' => 'rounded',
    'photo_size' => 'medium',
    'show_organization' => 1,
    'show_position' => 1,
    'show_category' => 1,
    'show_innovation_summary' => 1,
    'show_full_bio' => 1,
    'form_style' => 'cards',
    'scoring_style' => 'slider',
    'enable_animations' => 1,
    'enable_hover_effects' => 1
]);

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
                           min="0" max="10" step="0.5" class="small-text">
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
                           min="0" max="10" step="0.5" class="small-text">
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
                           min="0" max="10" step="0.5" class="small-text">
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
                           min="0" max="10" step="0.5" class="small-text">
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
                           min="0" max="10" step="0.5" class="small-text">
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
                        <option value="gradient" <?php selected(isset($dashboard_settings['header_style']) ? $dashboard_settings['header_style'] : 'gradient', 'gradient'); ?>><?php _e('Gradient (Default)', 'mobility-trailblazers'); ?></option>
                        <option value="solid" <?php selected(isset($dashboard_settings['header_style']) ? $dashboard_settings['header_style'] : 'gradient', 'solid'); ?>><?php _e('Solid Color', 'mobility-trailblazers'); ?></option>
                        <option value="image" <?php selected(isset($dashboard_settings['header_style']) ? $dashboard_settings['header_style'] : 'gradient', 'image'); ?>><?php _e('Background Image', 'mobility-trailblazers'); ?></option>
                    </select>
                    <p class="description"><?php _e('Choose the visual style for the dashboard header.', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            
            <!-- Primary Color -->
            <tr>
                <th scope="row">
                    <label for="dashboard_primary_color"><?php _e('Primary Color', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="color" id="dashboard_primary_color" name="mt_dashboard_settings[primary_color]"
                           value="<?php echo esc_attr(isset($dashboard_settings['primary_color']) ? $dashboard_settings['primary_color'] : '#0073aa'); ?>" />
                    <p class="description"><?php _e('Primary color for the dashboard theme', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            
            <!-- Header Image URL -->
            <tr>
                <th scope="row">
                    <label for="header_image_url"><?php _e('Header Background Image', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="text" id="header_image_url" name="mt_dashboard_settings[header_image_url]"
                           value="<?php echo esc_attr(isset($dashboard_settings['header_image_url']) ? $dashboard_settings['header_image_url'] : ''); ?>"
                           class="regular-text" />
                    <button type="button" id="upload_header_image" class="button button-secondary">
                        <?php _e('Choose Image', 'mobility-trailblazers'); ?>
                    </button>
                    <?php if (!empty($dashboard_settings['header_image_url'])) : ?>
                    <div class="mt-image-preview">
                        <img id="header_image_preview" src="<?php echo esc_url($dashboard_settings['header_image_url']); ?>"
                             alt="Header preview" style="max-width: 200px; margin-top: 10px;" />
                        <button type="button" class="button mt-clear-image" style="margin-top: 10px;"><?php _e('Remove Image', 'mobility-trailblazers'); ?></button>
                    </div>
                    <?php else : ?>
                        <div class="mt-image-preview" style="display: none;">
                            <img id="header_image_preview" src="" alt="<?php _e('Header background preview', 'mobility-trailblazers'); ?>" 
                                 style="max-width: 200px; height: auto; margin-top: 10px;" />
                            <button type="button" class="button mt-clear-image" style="margin-top: 10px; display: none;"><?php _e('Remove Image', 'mobility-trailblazers'); ?></button>
                        </div>
                    <?php endif; ?>
                    <p class="description"><?php _e('URL for header background image (optional)', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            
            <!-- Progress Bar Style -->
            <tr>
                <th scope="row">
                    <label for="progress_bar_style"><?php _e('Progress Bar Style', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <select id="progress_bar_style" name="mt_dashboard_settings[progress_bar_style]">
                        <option value="rounded" <?php selected(isset($dashboard_settings['progress_bar_style']) ? $dashboard_settings['progress_bar_style'] : 'rounded', 'rounded'); ?>><?php _e('Rounded', 'mobility-trailblazers'); ?></option>
                        <option value="square" <?php selected(isset($dashboard_settings['progress_bar_style']) ? $dashboard_settings['progress_bar_style'] : 'rounded', 'square'); ?>><?php _e('Square', 'mobility-trailblazers'); ?></option>
                        <option value="striped" <?php selected(isset($dashboard_settings['progress_bar_style']) ? $dashboard_settings['progress_bar_style'] : 'rounded', 'striped'); ?>><?php _e('Striped', 'mobility-trailblazers'); ?></option>
                    </select>
                </td>
            </tr>
            
            <!-- Display Options -->
            <tr>
                <th scope="row"><?php _e('Display Options', 'mobility-trailblazers'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="mt_dashboard_settings[show_welcome_message]" value="1"
                               <?php checked(isset($dashboard_settings['show_welcome_message']) ? $dashboard_settings['show_welcome_message'] : 1, 1); ?> />
                        <?php _e('Show welcome message', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_dashboard_settings[show_progress_bar]" value="1"
                               <?php checked(isset($dashboard_settings['show_progress_bar']) ? $dashboard_settings['show_progress_bar'] : 1, 1); ?> />
                        <?php _e('Show progress bar', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_dashboard_settings[show_stats_cards]" value="1"
                               <?php checked(isset($dashboard_settings['show_stats_cards']) ? $dashboard_settings['show_stats_cards'] : 1, 1); ?> />
                        <?php _e('Show statistics cards', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_dashboard_settings[show_search_filter]" value="1"
                               <?php checked(isset($dashboard_settings['show_search_filter']) ? $dashboard_settings['show_search_filter'] : 1, 1); ?> />
                        <?php _e('Show search and filter options', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_dashboard_settings[show_rankings]" value="1" 
                               <?php checked(isset($dashboard_settings['show_rankings']) ? $dashboard_settings['show_rankings'] : 1, 1); ?> />
                        <?php _e('Show rankings section', 'mobility-trailblazers'); ?>
                    </label>
                </td>
            </tr>
            
            <!-- Rankings Settings -->
            <tr>
                <th scope="row">
                    <label for="rankings_limit"><?php _e('Number of Rankings to Show', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <input type="number" name="mt_dashboard_settings[rankings_limit]" id="rankings_limit" 
                           value="<?php echo esc_attr(isset($dashboard_settings['rankings_limit']) ? $dashboard_settings['rankings_limit'] : 10); ?>" 
                           min="5" max="20" class="small-text">
                    <p class="description"><?php _e('How many top candidates to display in the rankings section', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            
            <!-- Cards Layout -->
            <tr>
                <th scope="row">
                    <label for="candidate_card_layout"><?php _e('Candidate Card Layout', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <select id="candidate_card_layout" name="mt_dashboard_settings[card_layout]">
                        <option value="grid" <?php selected(isset($dashboard_settings['card_layout']) ? $dashboard_settings['card_layout'] : 'grid', 'grid'); ?>><?php _e('Grid View', 'mobility-trailblazers'); ?></option>
                        <option value="list" <?php selected(isset($dashboard_settings['card_layout']) ? $dashboard_settings['card_layout'] : 'grid', 'list'); ?>><?php _e('List View', 'mobility-trailblazers'); ?></option>
                        <option value="compact" <?php selected(isset($dashboard_settings['card_layout']) ? $dashboard_settings['card_layout'] : 'grid', 'compact'); ?>><?php _e('Compact View', 'mobility-trailblazers'); ?></option>
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
                        echo esc_textarea(isset($dashboard_settings['intro_text']) ? $dashboard_settings['intro_text'] : ''); 
                    ?></textarea>
                    <p class="description"><?php _e('Custom message displayed at the top of the jury dashboard.', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
        </table>
        
        <!-- Language Settings -->
        <h2><?php _e('Language Settings', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Configure multilingual settings for the platform.', 'mobility-trailblazers'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="mt_default_language"><?php _e('Default Language', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <select id="mt_default_language" name="mt_default_language">
                        <option value="de_DE" <?php selected(get_option('mt_default_language', 'de_DE'), 'de_DE'); ?>>🇩🇪 Deutsch</option>
                        <option value="en_US" <?php selected(get_option('mt_default_language', 'de_DE'), 'en_US'); ?>>🇬🇧 English</option>
                    </select>
                    <p class="description"><?php _e('Select the default language for the platform.', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Language Options', 'mobility-trailblazers'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="mt_enable_language_switcher" value="1"
                               <?php checked(get_option('mt_enable_language_switcher', '1'), '1'); ?> />
                        <?php _e('Enable Language Switcher', 'mobility-trailblazers'); ?>
                    </label>
                    <p class="description"><?php _e('Show language switcher in the frontend.', 'mobility-trailblazers'); ?></p>
                    
                    <br/>
                    
                    <label>
                        <input type="checkbox" name="mt_auto_detect_language" value="1"
                               <?php checked(get_option('mt_auto_detect_language', '1'), '1'); ?> />
                        <?php _e('Auto-detect Language', 'mobility-trailblazers'); ?>
                    </label>
                    <p class="description"><?php _e('Automatically detect user language based on browser settings.', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
        </table>
        
        <!-- Candidate Presentation Customization -->
        <h2><?php _e('Candidate Presentation Settings', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Customize how candidates are displayed to jury members during evaluation.', 'mobility-trailblazers'); ?></p>

        <table class="form-table">
            <!-- Candidate Profile Layout -->
            <tr>
                <th scope="row">
                    <label for="candidate_profile_layout"><?php _e('Candidate Profile Layout', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <select id="candidate_profile_layout" name="mt_candidate_presentation[profile_layout]">
                        <option value="side-by-side" <?php selected(isset($candidate_presentation['profile_layout']) ? $candidate_presentation['profile_layout'] : 'side-by-side', 'side-by-side'); ?>><?php _e('Side by Side (Photo + Details)', 'mobility-trailblazers'); ?></option>
                        <option value="stacked" <?php selected(isset($candidate_presentation['profile_layout']) ? $candidate_presentation['profile_layout'] : 'side-by-side', 'stacked'); ?>><?php _e('Stacked (Photo above Details)', 'mobility-trailblazers'); ?></option>
                        <option value="card" <?php selected(isset($candidate_presentation['profile_layout']) ? $candidate_presentation['profile_layout'] : 'side-by-side', 'card'); ?>><?php _e('Card Style', 'mobility-trailblazers'); ?></option>
                        <option value="minimal" <?php selected(isset($candidate_presentation['profile_layout']) ? $candidate_presentation['profile_layout'] : 'side-by-side', 'minimal'); ?>><?php _e('Minimal (Text Only)', 'mobility-trailblazers'); ?></option>
                    </select>
                </td>
            </tr>
            
            <!-- Photo Style -->
            <tr>
                <th scope="row">
                    <label for="candidate_photo_style"><?php _e('Candidate Photo Style', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <select id="candidate_photo_style" name="mt_candidate_presentation[photo_style]">
                        <option value="square" <?php selected(isset($candidate_presentation['photo_style']) ? $candidate_presentation['photo_style'] : 'rounded', 'square'); ?>><?php _e('Square', 'mobility-trailblazers'); ?></option>
                        <option value="circle" <?php selected(isset($candidate_presentation['photo_style']) ? $candidate_presentation['photo_style'] : 'rounded', 'circle'); ?>><?php _e('Circle', 'mobility-trailblazers'); ?></option>
                        <option value="rounded" <?php selected(isset($candidate_presentation['photo_style']) ? $candidate_presentation['photo_style'] : 'rounded', 'rounded'); ?>><?php _e('Rounded Corners', 'mobility-trailblazers'); ?></option>
                    </select>
                    
                    <br/><br/>
                    
                    <label for="candidate_photo_size"><?php _e('Photo Size', 'mobility-trailblazers'); ?></label>
                    <select id="candidate_photo_size" name="mt_candidate_presentation[photo_size]">
                        <option value="small" <?php selected(isset($candidate_presentation['photo_size']) ? $candidate_presentation['photo_size'] : 'medium', 'small'); ?>><?php _e('Small (150px)', 'mobility-trailblazers'); ?></option>
                        <option value="medium" <?php selected(isset($candidate_presentation['photo_size']) ? $candidate_presentation['photo_size'] : 'medium', 'medium'); ?>><?php _e('Medium (200px)', 'mobility-trailblazers'); ?></option>
                        <option value="large" <?php selected(isset($candidate_presentation['photo_size']) ? $candidate_presentation['photo_size'] : 'medium', 'large'); ?>><?php _e('Large (300px)', 'mobility-trailblazers'); ?></option>
                    </select>
                </td>
            </tr>
            
            <!-- Display Options -->
            <tr>
                <th scope="row"><?php _e('Candidate Information Display', 'mobility-trailblazers'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="mt_candidate_presentation[show_organization]" value="1" 
                               <?php checked(isset($candidate_presentation['show_organization']) ? $candidate_presentation['show_organization'] : 1, 1); ?> />
                        <?php _e('Show Organization/Company', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_candidate_presentation[show_position]" value="1" 
                               <?php checked(isset($candidate_presentation['show_position']) ? $candidate_presentation['show_position'] : 1, 1); ?> />
                        <?php _e('Show Position/Title', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_candidate_presentation[show_category]" value="1" 
                               <?php checked(isset($candidate_presentation['show_category']) ? $candidate_presentation['show_category'] : 1, 1); ?> />
                        <?php _e('Show Award Category', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_candidate_presentation[show_innovation_summary]" value="1" 
                               <?php checked(isset($candidate_presentation['show_innovation_summary']) ? $candidate_presentation['show_innovation_summary'] : 1, 1); ?> />
                        <?php _e('Show Innovation Summary', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_candidate_presentation[show_full_bio]" value="1" 
                               <?php checked(isset($candidate_presentation['show_full_bio']) ? $candidate_presentation['show_full_bio'] : 1, 1); ?> />
                        <?php _e('Show Full Biography', 'mobility-trailblazers'); ?>
                    </label>
                </td>
            </tr>
            
            <!-- Evaluation Form Style -->
            <tr>
                <th scope="row">
                    <label for="evaluation_form_style"><?php _e('Evaluation Form Style', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <select id="evaluation_form_style" name="mt_candidate_presentation[form_style]">
                        <option value="cards" <?php selected(isset($candidate_presentation['form_style']) ? $candidate_presentation['form_style'] : 'cards', 'cards'); ?>><?php _e('Card-based Criteria', 'mobility-trailblazers'); ?></option>
                        <option value="list" <?php selected(isset($candidate_presentation['form_style']) ? $candidate_presentation['form_style'] : 'cards', 'list'); ?>><?php _e('List View', 'mobility-trailblazers'); ?></option>
                        <option value="compact" <?php selected(isset($candidate_presentation['form_style']) ? $candidate_presentation['form_style'] : 'cards', 'compact'); ?>><?php _e('Compact View', 'mobility-trailblazers'); ?></option>
                        <option value="wizard" <?php selected(isset($candidate_presentation['form_style']) ? $candidate_presentation['form_style'] : 'cards', 'wizard'); ?>><?php _e('Step-by-Step Wizard', 'mobility-trailblazers'); ?></option>
                    </select>
                </td>
            </tr>
            
            <!-- Scoring Display -->
            <tr>
                <th scope="row">
                    <label for="scoring_display_style"><?php _e('Score Display Style', 'mobility-trailblazers'); ?></label>
                </th>
                <td>
                    <select id="scoring_display_style" name="mt_candidate_presentation[scoring_style]">
                        <option value="slider" <?php selected(isset($candidate_presentation['scoring_style']) ? $candidate_presentation['scoring_style'] : 'slider', 'slider'); ?>><?php _e('Slider with Marks', 'mobility-trailblazers'); ?></option>
                        <option value="stars" <?php selected(isset($candidate_presentation['scoring_style']) ? $candidate_presentation['scoring_style'] : 'slider', 'stars'); ?>><?php _e('Star Rating', 'mobility-trailblazers'); ?></option>
                        <option value="numeric" <?php selected(isset($candidate_presentation['scoring_style']) ? $candidate_presentation['scoring_style'] : 'slider', 'numeric'); ?>><?php _e('Numeric Input', 'mobility-trailblazers'); ?></option>
                        <option value="buttons" <?php selected(isset($candidate_presentation['scoring_style']) ? $candidate_presentation['scoring_style'] : 'slider', 'buttons'); ?>><?php _e('Button Selection', 'mobility-trailblazers'); ?></option>
                    </select>
                </td>
            </tr>
            
            <!-- Animation Options -->
            <tr>
                <th scope="row"><?php _e('Animation & Effects', 'mobility-trailblazers'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="mt_candidate_presentation[enable_animations]" value="1" 
                               <?php checked(isset($candidate_presentation['enable_animations']) ? $candidate_presentation['enable_animations'] : 1, 1); ?> />
                        <?php _e('Enable smooth transitions and animations', 'mobility-trailblazers'); ?>
                    </label><br />
                    
                    <label>
                        <input type="checkbox" name="mt_candidate_presentation[enable_hover_effects]" value="1" 
                               <?php checked(isset($candidate_presentation['enable_hover_effects']) ? $candidate_presentation['enable_hover_effects'] : 1, 1); ?> />
                        <?php _e('Enable hover effects on interactive elements', 'mobility-trailblazers'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <!-- Enhanced Template Settings -->
        <h2><?php _e('Enhanced Candidate Profile Template', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Configure the enhanced candidate profile template with modern UI features.', 'mobility-trailblazers'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enhanced Template', 'mobility-trailblazers'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="mt_use_enhanced_template" value="1"
                               <?php checked(get_option('mt_use_enhanced_template', '1'), '1'); ?> />
                        <strong><?php _e('Use Enhanced Candidate Profile Template (v2.4.0)', 'mobility-trailblazers'); ?></strong>
                    </label>
                    <p class="description">
                        <?php _e('Enables the modern candidate profile template with hero sections, criteria cards, and enhanced UI elements.', 'mobility-trailblazers'); ?>
                    </p>
                    
                    <div class="notice notice-info inline" style="margin-top: 15px;">
                        <p><strong><?php _e('Enhanced Template Features:', 'mobility-trailblazers'); ?></strong></p>
                        <ul style="list-style-type: disc; margin-left: 20px;">
                            <li><?php _e('Hero section with gradient background and floating photo frame', 'mobility-trailblazers'); ?></li>
                            <li><?php _e('Structured evaluation criteria cards with custom icons', 'mobility-trailblazers'); ?></li>
                            <li><?php _e('Sidebar with quick facts and navigation', 'mobility-trailblazers'); ?></li>
                            <li><?php _e('Modern responsive design with animations', 'mobility-trailblazers'); ?></li>
                            <li><?php _e('Enhanced typography and visual hierarchy', 'mobility-trailblazers'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="notice notice-warning inline" style="margin-top: 10px;">
                        <p>
                            <strong><?php _e('Note:', 'mobility-trailblazers'); ?></strong>
                            <?php _e('To display structured criteria cards, run the criteria parsing tool:', 'mobility-trailblazers'); ?>
                            <br/>
                            <code>wp eval-file parse-evaluation-criteria.php</code>
                        </p>
                    </div>
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
        
        <!-- Data Management -->
        <h2><?php _e('Data Management', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Configure how plugin data is handled during various operations.', 'mobility-trailblazers'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Uninstall Options', 'mobility-trailblazers'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="mt_remove_data_on_uninstall" value="1"
                               <?php checked(get_option('mt_remove_data_on_uninstall', '0'), '1'); ?> />
                        <strong><?php _e('Remove all data when plugin is uninstalled', 'mobility-trailblazers'); ?></strong>
                    </label>
                    <div class="notice notice-warning inline" style="margin-top: 10px;">
                        <p>
                            <strong><?php _e('⚠️ WARNING:', 'mobility-trailblazers'); ?></strong> 
                            <?php _e('Checking this box will cause ALL plugin data to be PERMANENTLY DELETED when the plugin is uninstalled. This includes:', 'mobility-trailblazers'); ?>
                        </p>
                        <ul style="list-style-type: disc; margin-left: 20px;">
                            <li><?php _e('All candidate profiles and information', 'mobility-trailblazers'); ?></li>
                            <li><?php _e('All jury member profiles', 'mobility-trailblazers'); ?></li>
                            <li><?php _e('All evaluations and scores', 'mobility-trailblazers'); ?></li>
                            <li><?php _e('All assignments and relationships', 'mobility-trailblazers'); ?></li>
                            <li><?php _e('All audit logs and history', 'mobility-trailblazers'); ?></li>
                            <li><?php _e('All custom database tables (mt_*)', 'mobility-trailblazers'); ?></li>
                            <li><?php _e('All plugin settings and configurations', 'mobility-trailblazers'); ?></li>
                        </ul>
                        <p style="color: #d63638;">
                            <strong><?php _e('This action cannot be undone!', 'mobility-trailblazers'); ?></strong> 
                            <?php _e('Only enable this if you are absolutely certain you want to completely remove all traces of this plugin from your WordPress installation.', 'mobility-trailblazers'); ?>
                        </p>
                    </div>
                    <p class="description" style="margin-top: 10px;">
                        <?php _e('If unchecked (recommended), deactivating or uninstalling the plugin will preserve all data for potential future use.', 'mobility-trailblazers'); ?>
                    </p>
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