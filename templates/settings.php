<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user has permission
if (!current_user_can('mt_manage_awards')) {
    wp_die(__('You do not have permission to access this page.', 'mobility-trailblazers'));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mt_settings_nonce']) && wp_verify_nonce($_POST['mt_settings_nonce'], 'mt_settings')) {
    // Save current round
    if (isset($_POST['current_round'])) {
        update_option('mt_current_vote_round', intval($_POST['current_round']));
    }

    // Save voting period
    if (isset($_POST['voting_start']) && isset($_POST['voting_end'])) {
        update_option('mt_voting_start', sanitize_text_field($_POST['voting_start']));
        update_option('mt_voting_end', sanitize_text_field($_POST['voting_end']));
    }

    // Save evaluation criteria
    if (isset($_POST['evaluation_criteria']) && is_array($_POST['evaluation_criteria'])) {
        $criteria = array();
        foreach ($_POST['evaluation_criteria'] as $key => $criterion) {
            if (!empty($criterion['label'])) {
                $criteria[$key] = array(
                    'label' => sanitize_text_field($criterion['label']),
                    'description' => sanitize_textarea_field($criterion['description'])
                );
            }
        }
        update_option('mt_evaluation_criteria', $criteria);
    }

    // Show success message
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'mobility-trailblazers') . '</p></div>';
}

// Get current settings
$current_round = get_option('mt_current_vote_round', 1);
$voting_start = get_option('mt_voting_start', '');
$voting_end = get_option('mt_voting_end', '');
$evaluation_criteria = get_option('mt_evaluation_criteria', array(
    'innovation' => array(
        'label' => __('Innovation', 'mobility-trailblazers'),
        'description' => __('How innovative is the solution?', 'mobility-trailblazers')
    ),
    'impact' => array(
        'label' => __('Impact', 'mobility-trailblazers'),
        'description' => __('What is the potential impact of the solution?', 'mobility-trailblazers')
    ),
    'feasibility' => array(
        'label' => __('Feasibility', 'mobility-trailblazers'),
        'description' => __('How feasible is the implementation?', 'mobility-trailblazers')
    ),
    'sustainability' => array(
        'label' => __('Sustainability', 'mobility-trailblazers'),
        'description' => __('How sustainable is the solution?', 'mobility-trailblazers')
    ),
    'scalability' => array(
        'label' => __('Scalability', 'mobility-trailblazers'),
        'description' => __('How scalable is the solution?', 'mobility-trailblazers')
    )
));
?>

<div class="wrap">
    <h1><?php _e('Award System Settings', 'mobility-trailblazers'); ?></h1>

    <form method="post" class="mt-settings-form">
        <?php wp_nonce_field('mt_settings', 'mt_settings_nonce'); ?>

        <div class="mt-settings-section">
            <h2><?php _e('Voting Settings', 'mobility-trailblazers'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="current_round"><?php _e('Current Round', 'mobility-trailblazers'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="current_round" 
                               id="current_round" 
                               value="<?php echo esc_attr($current_round); ?>" 
                               min="1" 
                               required>
                        <p class="description">
                            <?php _e('The current voting round number.', 'mobility-trailblazers'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="voting_start"><?php _e('Voting Period', 'mobility-trailblazers'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" 
                               name="voting_start" 
                               id="voting_start" 
                               value="<?php echo esc_attr($voting_start); ?>">
                        <span class="mt-date-separator"><?php _e('to', 'mobility-trailblazers'); ?></span>
                        <input type="datetime-local" 
                               name="voting_end" 
                               id="voting_end" 
                               value="<?php echo esc_attr($voting_end); ?>">
                        <p class="description">
                            <?php _e('The period during which voting is allowed.', 'mobility-trailblazers'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="mt-settings-section">
            <h2><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h2>

            <div class="mt-criteria-container">
                <?php foreach ($evaluation_criteria as $key => $criterion): ?>
                    <div class="mt-criterion">
                        <h3><?php echo esc_html($criterion['label']); ?></h3>
                        
                        <div class="mt-criterion-fields">
                            <div class="mt-field">
                                <label for="criteria_<?php echo esc_attr($key); ?>_label">
                                    <?php _e('Label', 'mobility-trailblazers'); ?>
                                </label>
                                <input type="text" 
                                       name="evaluation_criteria[<?php echo esc_attr($key); ?>][label]" 
                                       id="criteria_<?php echo esc_attr($key); ?>_label" 
                                       value="<?php echo esc_attr($criterion['label']); ?>" 
                                       required>
                            </div>

                            <div class="mt-field">
                                <label for="criteria_<?php echo esc_attr($key); ?>_description">
                                    <?php _e('Description', 'mobility-trailblazers'); ?>
                                </label>
                                <textarea name="evaluation_criteria[<?php echo esc_attr($key); ?>][description]" 
                                          id="criteria_<?php echo esc_attr($key); ?>_description" 
                                          rows="3" 
                                          required><?php echo esc_textarea($criterion['description']); ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-submit">
            <button type="submit" class="button button-primary">
                <?php _e('Save Settings', 'mobility-trailblazers'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.mt-settings-section {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.mt-settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.mt-date-separator {
    margin: 0 10px;
}

.mt-criteria-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.mt-criterion {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
}

.mt-criterion h3 {
    margin-top: 0;
    margin-bottom: 15px;
}

.mt-criterion-fields {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.mt-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.mt-field input[type="text"],
.mt-field textarea {
    width: 100%;
}

.mt-submit {
    text-align: right;
    margin-top: 20px;
}
</style> 