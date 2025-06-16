<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user has permission
if (!current_user_can('mt_manage_voting')) {
    wp_die(__('You do not have permission to access this page.', 'mobility-trailblazers'));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mt_vote_reset_nonce']) && wp_verify_nonce($_POST['mt_vote_reset_nonce'], 'mt_vote_reset')) {
    $reset_type = sanitize_text_field($_POST['reset_type']);
    $reset_reason = sanitize_textarea_field($_POST['reset_reason']);
    $affected_user_id = isset($_POST['affected_user_id']) ? intval($_POST['affected_user_id']) : null;

    // Log the reset action
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'vote_reset_logs',
        array(
            'initiated_by' => get_current_user_id(),
            'affected_user_id' => $affected_user_id,
            'reset_type' => $reset_type,
            'reset_reason' => $reset_reason,
            'created_at' => current_time('mysql')
        )
    );

    // Perform the reset based on type
    switch ($reset_type) {
        case 'all_votes':
            // Reset all votes
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}mt_votes");
            break;

        case 'user_votes':
            if ($affected_user_id) {
                // Reset votes for specific user
                $wpdb->delete(
                    $wpdb->prefix . 'mt_votes',
                    array('jury_member_id' => $affected_user_id)
                );
            }
            break;

        case 'round_votes':
            $round = intval($_POST['round']);
            // Reset votes for specific round
            $wpdb->delete(
                $wpdb->prefix . 'mt_votes',
                array('vote_round' => $round)
            );
            break;
    }

    // Show success message
    echo '<div class="notice notice-success"><p>' . __('Votes have been reset successfully.', 'mobility-trailblazers') . '</p></div>';
}

// Get current round
$current_round = get_option('mt_current_vote_round', 1);

// Get jury members
$jury_members = get_posts(array(
    'post_type' => 'mt_jury',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));
?>

<div class="wrap">
    <h1><?php _e('Vote Reset Management', 'mobility-trailblazers'); ?></h1>

    <div class="mt-vote-reset-container">
        <form method="post" class="mt-vote-reset-form">
            <?php wp_nonce_field('mt_vote_reset', 'mt_vote_reset_nonce'); ?>

            <div class="mt-reset-options">
                <h2><?php _e('Reset Options', 'mobility-trailblazers'); ?></h2>

                <div class="mt-reset-option">
                    <label>
                        <input type="radio" name="reset_type" value="all_votes" required>
                        <?php _e('Reset All Votes', 'mobility-trailblazers'); ?>
                    </label>
                    <p class="description"><?php _e('This will reset all votes across all rounds.', 'mobility-trailblazers'); ?></p>
                </div>

                <div class="mt-reset-option">
                    <label>
                        <input type="radio" name="reset_type" value="user_votes">
                        <?php _e('Reset Votes for Specific Jury Member', 'mobility-trailblazers'); ?>
                    </label>
                    <select name="affected_user_id" class="mt-user-select" disabled>
                        <option value=""><?php _e('Select Jury Member', 'mobility-trailblazers'); ?></option>
                        <?php foreach ($jury_members as $jury): ?>
                            <option value="<?php echo esc_attr($jury->ID); ?>">
                                <?php echo esc_html($jury->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mt-reset-option">
                    <label>
                        <input type="radio" name="reset_type" value="round_votes">
                        <?php _e('Reset Votes for Specific Round', 'mobility-trailblazers'); ?>
                    </label>
                    <select name="round" class="mt-round-select" disabled>
                        <?php for ($i = 1; $i <= $current_round; $i++): ?>
                            <option value="<?php echo esc_attr($i); ?>">
                                <?php printf(__('Round %d', 'mobility-trailblazers'), $i); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="mt-reset-reason">
                <h2><?php _e('Reset Reason', 'mobility-trailblazers'); ?></h2>
                <textarea name="reset_reason" rows="4" required placeholder="<?php esc_attr_e('Please provide a reason for resetting votes...', 'mobility-trailblazers'); ?>"></textarea>
            </div>

            <div class="mt-submit">
                <button type="submit" class="button button-primary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to reset these votes? This action cannot be undone.', 'mobility-trailblazers'); ?>');">
                    <?php _e('Reset Votes', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.mt-vote-reset-container {
    max-width: 800px;
    margin-top: 20px;
}

.mt-reset-options {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.mt-reset-option {
    margin-bottom: 20px;
}

.mt-reset-option label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.mt-reset-option .description {
    color: #666;
    font-size: 0.9em;
    margin-top: 5px;
}

.mt-user-select,
.mt-round-select {
    margin-top: 10px;
    width: 100%;
    max-width: 400px;
}

.mt-reset-reason {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.mt-reset-reason textarea {
    width: 100%;
    margin-top: 10px;
}

.mt-submit {
    text-align: right;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Enable/disable select fields based on radio selection
    $('input[name="reset_type"]').change(function() {
        $('.mt-user-select, .mt-round-select').prop('disabled', true);
        
        if ($(this).val() === 'user_votes') {
            $('.mt-user-select').prop('disabled', false);
        } else if ($(this).val() === 'round_votes') {
            $('.mt-round-select').prop('disabled', false);
        }
    });
});
</script> 