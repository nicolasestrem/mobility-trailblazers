<?php
/**
 * Template for displaying candidate grid
 */
?>
<div class="mt-candidate-grid" style="grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);">
    <?php while ($candidates->have_posts()): $candidates->the_post(); 
        $candidate_id = get_the_ID();
        $thumbnail = get_the_post_thumbnail_url($candidate_id, 'medium');
        $company = get_post_meta($candidate_id, 'company', true);
        $position = get_post_meta($candidate_id, 'position', true);
    ?>
        <div class="mt-candidate-card">
            <?php if ($thumbnail) : ?>
                <div class="mt-candidate-image">
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                </div>
            <?php endif; ?>
            
            <div class="mt-candidate-content">
                <h3 class="mt-candidate-title"><?php echo esc_html(get_the_title()); ?></h3>
                <?php if ($company) : ?>
                    <div class="mt-candidate-company"><?php echo esc_html($company); ?></div>
                <?php endif; ?>
                <?php if ($position) : ?>
                    <div class="mt-candidate-position"><?php echo esc_html($position); ?></div>
                <?php endif; ?>
                
                <?php if ($show_voting) : ?>
                    <div class="mt-candidate-voting">
                        <?php echo do_shortcode('[mt_voting_form candidate_id="' . $candidate_id . '"]'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>
<?php wp_reset_postdata(); ?> 