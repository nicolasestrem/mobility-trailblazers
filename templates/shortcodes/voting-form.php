<?php
/**
 * Template for displaying voting form
 */
?>
<div class="mt-voting-form">
    <form id="mt-voting-form-<?php echo esc_attr($candidate->ID); ?>" class="mt-vote-form">
        <?php wp_nonce_field('mt_vote_nonce', 'mt_vote_nonce'); ?>
        <input type="hidden" name="candidate_id" value="<?php echo esc_attr($candidate->ID); ?>">
        <input type="hidden" name="vote_type" value="<?php echo esc_attr($vote_type); ?>">
        
        <?php if ($show_criteria) : ?>
            <div class="mt-voting-criteria">
                <?php foreach ($criteria as $criterion) : ?>
                    <div class="mt-criterion">
                        <label for="criterion_<?php echo esc_attr($criterion->ID); ?>">
                            <?php echo esc_html($criterion->name); ?>
                        </label>
                        <select name="criteria[<?php echo esc_attr($criterion->ID); ?>]" 
                                id="criterion_<?php echo esc_attr($criterion->ID); ?>" required>
                            <option value=""><?php _e('Select rating', 'mobility-trailblazers'); ?></option>
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-voting-comments">
            <label for="comments"><?php _e('Comments (optional)', 'mobility-trailblazers'); ?></label>
            <textarea name="comments" id="comments" rows="3"></textarea>
        </div>
        
        <div class="mt-vote-message"></div>
        
        <button type="submit" class="mt-submit-vote">
            <?php _e('Submit Vote', 'mobility-trailblazers'); ?>
        </button>
    </form>
</div> 