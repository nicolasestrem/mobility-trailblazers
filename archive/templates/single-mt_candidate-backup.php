<?php
/**
 * Single Candidate Template
 *
 * @package MobilityTrailblazers
 * @since 2.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) : the_post();
    $candidate_id = get_the_ID();
    $organization = get_post_meta($candidate_id, '_mt_organization', true);
    $position = get_post_meta($candidate_id, '_mt_position', true);
    $display_name = get_post_meta($candidate_id, '_mt_display_name', true) ?: get_the_title();
    $overview = get_post_meta($candidate_id, '_mt_overview', true);
    $eval_criteria = get_post_meta($candidate_id, '_mt_evaluation_criteria', true);
    $personality = get_post_meta($candidate_id, '_mt_personality_motivation', true);
    $linkedin = get_post_meta($candidate_id, '_mt_linkedin', true);
    $website = get_post_meta($candidate_id, '_mt_website', true);
    $categories = wp_get_post_terms($candidate_id, 'mt_award_category');
?>

<div class="mt-candidate-profile-page">
    <div class="mt-profile-header">
        <div class="mt-profile-pattern"></div>
        <div class="mt-container">
            <div class="mt-profile-intro">
                <div class="mt-profile-photo-wrap">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('large', ['class' => 'mt-profile-photo']); ?>
                    <?php else : ?>
                        <div class="mt-profile-photo-placeholder">
                            <span class="dashicons dashicons-businessman"></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-profile-info">
                    <h1 class="mt-profile-name"><?php echo esc_html($display_name); ?></h1>
                    <?php if ($position || $organization) : ?>
                        <div class="mt-profile-title">
                            <?php if ($position) : ?>
                                <span class="mt-position"><?php echo esc_html($position); ?></span>
                            <?php endif; ?>
                            <?php if ($position && $organization) : ?>
                                <span class="mt-separator">–</span>
                            <?php endif; ?>
                            <?php if ($organization) : ?>
                                <span class="mt-organization"><?php echo esc_html($organization); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($categories)) : ?>
                        <div class="mt-profile-categories">
                            <?php foreach ($categories as $category) : ?>
                                <span class="mt-category-badge"><?php echo esc_html($category->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-profile-links">
                        <?php if ($linkedin) : ?>
                            <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener">
                                <span class="dashicons dashicons-linkedin"></span>
                                LinkedIn
                            </a>
                        <?php endif; ?>
                        <?php if ($website) : ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener">
                                <span class="dashicons dashicons-admin-links"></span>
                                Website
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-profile-content">
        <div class="mt-container">
            <div class="mt-profile-sections">
                <?php if ($overview) : ?>
                    <section class="mt-profile-section">
                        <h2 class="mt-section-title">
                            <span class="mt-section-label"><?php _e('Überblick:', 'mobility-trailblazers'); ?></span>
                        </h2>
                        <div class="mt-section-content">
                            <?php echo wp_kses_post($overview); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($eval_criteria) : ?>
                    <section class="mt-profile-section">
                        <h2 class="mt-section-title">
                            <span class="mt-section-label"><?php _e('Bewertung nach Kriterien:', 'mobility-trailblazers'); ?></span>
                        </h2>
                        <div class="mt-section-content">
                            <?php echo wp_kses_post($eval_criteria); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($personality) : ?>
                    <section class="mt-profile-section">
                        <h2 class="mt-section-title">
                            <span class="mt-section-label"><?php _e('Persönlichkeit & Motivation:', 'mobility-trailblazers'); ?></span>
                        </h2>
                        <div class="mt-section-content">
                            <?php echo wp_kses_post($personality); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if (!$overview && !$eval_criteria && !$personality) : ?>
                    <div class="mt-notice mt-notice-info">
                        <p><?php _e('The detailed profile information for this candidate is being prepared.', 'mobility-trailblazers'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (current_user_can('mt_submit_evaluations')) : ?>
                <div class="mt-profile-actions">
                    <a href="<?php echo esc_url(add_query_arg('evaluate', $candidate_id, home_url('/jury-dashboard/'))); ?>" 
                       class="mt-btn mt-btn-primary">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php _e('Evaluate This Candidate', 'mobility-trailblazers'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <div class="mt-profile-navigation">
                <?php
                $prev_post = get_previous_post(true, '', 'mt_award_category');
                $next_post = get_next_post(true, '', 'mt_award_category');
                ?>
                
                <?php if ($prev_post) : ?>
                    <a href="<?php echo get_permalink($prev_post); ?>" class="mt-nav-prev">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                        <?php _e('Previous Candidate', 'mobility-trailblazers'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($next_post) : ?>
                    <a href="<?php echo get_permalink($next_post); ?>" class="mt-nav-next">
                        <?php _e('Next Candidate', 'mobility-trailblazers'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endwhile;

get_footer(); ?>
