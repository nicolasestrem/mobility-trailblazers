// templates/jury/vote-reset-button.php
<?php
/**
 * Vote Reset Button Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$can_reset = apply_filters('mt_can_reset_vote', true, $candidate_id, get_current_user_id());
$has_voted = MT_Voting_System::has_voted($candidate_id, get_current_user_id());
?>

<?php if ($has_voted && $can_reset): ?>
    <button type="button" 
            class="mt-reset-vote-btn btn btn-sm btn-outline-danger"
            data-candidate-id="<?php echo esc_attr($candidate_id); ?>"
            data-candidate-name="<?php echo esc_attr($candidate_name); ?>"
            title="<?php esc_attr_e('Reset your vote for this candidate', 'mobility-trailblazers'); ?>">
        <i class="fas fa-undo"></i> 
        <?php _e('Reset Vote', 'mobility-trailblazers'); ?>
    </button>
<?php endif; ?>